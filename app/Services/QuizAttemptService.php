<?php
declare(strict_types=1);
namespace App\Services;
use App\Domain\ScoreCalculator;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class QuizAttemptService
{
    public function __construct(private readonly ScoreCalculator $scores, private readonly AuditService $audit) {}
    public function start(User $user, Quiz $quiz): Attempt
    {
        return DB::transaction(function () use ($user, $quiz) {
            // Serialize starts per learner, including double clicks and parallel tabs.
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!$user->is_active || $user->isAdmin()) {
                throw ValidationException::withMessages(['quiz'=>'Only active learner accounts can start attempts.']);
            }
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $existing = Attempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->where('status', 'in_progress')->lockForUpdate()->first();
            if ($existing) { return $this->expireLocked($existing); }
            if (!$quiz->is_published || !$quiz->category->is_active || $quiz->category->trashed() || !$quiz->availableTo($user)) {
                throw ValidationException::withMessages(['quiz'=>'This quiz is not available to your account.']);
            }
            if (Attempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count() >= $quiz->max_attempts) {
                throw ValidationException::withMessages(['quiz'=>'You have reached the attempt limit for this quiz.']);
            }
            $query = Question::where('category_id', $quiz->category_id)->where('is_active', true);
            $questions = ($quiz->shuffle_questions ? $query->inRandomOrder() : $query->orderBy('id'))->limit($quiz->question_count)->get();
            if ($questions->count() !== $quiz->question_count) {
                throw ValidationException::withMessages(['quiz'=>'There are not enough active questions. Please contact your administrator.']);
            }
            $started = now();
            $attempt = Attempt::create([
                'uuid'=>(string) Str::uuid(), 'user_id'=>$user->id, 'quiz_id'=>$quiz->id,
                'quiz_title'=>$quiz->title, 'category_name'=>$quiz->category->name, 'status'=>'in_progress',
                'total_questions'=>$questions->count(), 'pass_percentage'=>$quiz->pass_percentage,
                'review_answers'=>$quiz->review_answers, 'started_at'=>$started, 'expires_at'=>$started->copy()->addMinutes($quiz->duration_minutes),
            ]);
            foreach ($questions as $index=>$question) {
                // JSON arrays retain order on SQLite, MySQL and PostgreSQL. JSON object keys may not.
                $options = [];
                foreach ($question->options as $key=>$text) { $options[] = ['key'=>(string) $key, 'text'=>$text]; }
                if ($quiz->shuffle_options) { shuffle($options); }
                $attempt->questions()->create([
                    'question_id'=>$question->id, 'sequence'=>$index+1, 'prompt'=>$question->prompt,
                    'options'=>$options, 'correct_option'=>$question->correct_option, 'explanation'=>$question->explanation,
                ]);
            }
            $this->audit->record('started', $attempt, 'Quiz attempt started: '.$quiz->title);
            return $attempt;
        }, 3);
    }
    public function answer(Attempt $attempt, int $questionId, ?string $selected): Attempt
    {
        return DB::transaction(function () use ($attempt, $questionId, $selected) {
            $locked = Attempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $locked = $this->expireLocked($locked);
            if ($locked->isComplete()) { return $locked; }
            // Never accept an item belonging to another attempt.
            $question = $locked->questions()->whereKey($questionId)->firstOrFail();
            if ($selected !== null && !in_array($selected, array_column($question->options, 'key'), true)) {
                throw ValidationException::withMessages(['selected_option'=>'Choose one of the displayed answers.']);
            }
            $question->update(['selected_option'=>$selected, 'answered_at'=>$selected === null ? null : now()]);
            return $locked;
        }, 3);
    }
    public function finalize(Attempt $attempt, bool $force = false): Attempt
    {
        return DB::transaction(function () use ($attempt, $force) {
            $locked = Attempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($locked->isComplete()) { return $locked; }
            $expired = now()->greaterThanOrEqualTo($locked->expires_at);
            return ($force || $expired) ? $this->gradeLocked($locked, $expired) : $locked;
        }, 3);
    }
    private function expireLocked(Attempt $attempt): Attempt
    {
        return !$attempt->isComplete() && now()->greaterThanOrEqualTo($attempt->expires_at) ? $this->gradeLocked($attempt, true) : $attempt;
    }
    private function gradeLocked(Attempt $attempt, bool $expired): Attempt
    {
        $answers = $attempt->questions()->get()->map(fn ($q) => ['selected'=>$q->selected_option, 'correct'=>$q->correct_option]);
        $score = $this->scores->calculate($answers, $attempt->pass_percentage);
        $attempt->update(['status'=>$expired ? 'expired' : 'submitted', 'correct_count'=>$score['correct'], 'percentage'=>$score['percentage'], 'passed'=>$score['passed'], 'submitted_at'=>$expired ? $attempt->expires_at : now()]);
        $this->audit->record($expired ? 'expired' : 'submitted', $attempt, 'Quiz attempt finalized');
        return $attempt;
    }
}
