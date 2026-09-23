<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Http\Requests\AnswerRequest;
use App\Models\Attempt;
use App\Services\QuizAttemptService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
final class AttemptController extends Controller
{
    public function index(Request $request)
    {
        $f = ListFilters::read($request); $q = $request->user()->attempts();
        ListFilters::search($q, $f['q'] ?? null, ['quiz_title','category_name']);
        if (in_array($f['status'] ?? '', ['in_progress','submitted','expired'], true)) { $q->where('status',$f['status']); }
        return $this->pages->render('attempts.index', ['attempts'=>$q->latest()->paginate($f['per_page'] ?? 10)->withQueryString()], 'My results');
    }
    public function show(Request $request, Attempt $attempt, QuizAttemptService $service)
    {
        Gate::authorize('view',$attempt);
        $attempt = $service->finalize($attempt);
        if ($attempt->isComplete()) {
            return $this->pages->render('attempts.result', ['attempt'=>$attempt->load('questions','user'), 'canReview'=>$attempt->review_answers || $request->user()->isAdmin()], 'Quiz result');
        }
        // Admin can inspect progress, but cannot answer on a learner's behalf.
        $data = $request->validate(['question'=>['nullable','integer','min:1','max:'.$attempt->total_questions]]);
        $position = (int) ($data['question'] ?? 1);
        $items = $attempt->questions()->get();
        return $this->pages->render('attempts.take', ['attempt'=>$attempt, 'items'=>$items, 'item'=>$items->firstWhere('sequence',$position), 'position'=>$position, 'readOnly'=>$request->user()->isAdmin()], $attempt->quiz_title);
    }
    public function answer(AnswerRequest $request, Attempt $attempt, int $question, QuizAttemptService $service)
    {
        $attempt = $service->answer($attempt, $question, $request->validated('selected_option'));
        if ($attempt->isComplete()) { return $this->pages->saved(route('attempts.show',$attempt), 'The attempt has ended.'); }
        $items = $attempt->questions()->get();
        $position = $items->firstWhere('id', $question)?->sequence;
        return response()->json(['message'=>'Answer saved', 'fragments'=>['attempt-summary'=>view('attempts.summary', compact('attempt','items','position'))->render()], 'csrf_token'=>csrf_token()]);
    }
    public function submit(Request $request, Attempt $attempt, QuizAttemptService $service)
    {
        Gate::authorize('answer',$attempt);
        $attempt = $service->finalize($attempt, true);
        return $this->pages->saved(route('attempts.show',$attempt), 'Your answers have been submitted.');
    }
}
