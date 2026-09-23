<?php
declare(strict_types=1);
namespace App\Http\Requests;
use App\Models\Question;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
final class QuizRequest extends AdminRequest
{
    public function rules(): array { return [
        'category_id'=>['required','integer',Rule::exists('categories','id')->whereNull('deleted_at')], 'title'=>['required','string','max:150'],
        'description'=>['nullable','string','max:4000'], 'question_count'=>['required','integer','min:1','max:200'],
        'duration_minutes'=>['required','integer','min:1','max:240'], 'pass_percentage'=>['required','integer','min:1','max:100'],
        'max_attempts'=>['required','integer','min:1','max:100'], 'shuffle_questions'=>['required','boolean'], 'shuffle_options'=>['required','boolean'],
        'review_answers'=>['required','boolean'], 'is_published'=>['required','boolean'], 'access_mode'=>['required','in:all,assigned'],
        'user_ids'=>['required_if:access_mode,assigned','array','max:2000'],
        'user_ids.*'=>['integer','distinct',Rule::exists('users','id')->where('role','learner')->whereNull('deleted_at')],
    ]; }
    public function after(): array { return [function (Validator $validator) {
        if ($validator->errors()->isNotEmpty() || !$this->boolean('is_published')) { return; }
        $count = Question::where('category_id', $this->integer('category_id'))->where('is_active', true)->count();
        if ($count < $this->integer('question_count')) { $validator->errors()->add('question_count', "Only {$count} active questions are available. Add questions or lower the question count."); }
    }]; }
}
