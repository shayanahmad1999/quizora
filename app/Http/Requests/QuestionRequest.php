<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class QuestionRequest extends AdminRequest
{
    public function rules(): array
    {
        $rules = ['category_id'=>['required','integer',Rule::exists('categories','id')->whereNull('deleted_at')],
            'prompt'=>['required','string','max:10000'], 'correct_option'=>['required','in:a,b,c,d'],
            'explanation'=>['nullable','string','max:10000'], 'difficulty'=>['required','in:easy,medium,hard'], 'is_active'=>['required','boolean']];
        foreach (['a','b','c','d'] as $key) { $rules['option_'.$key] = ['required','string','max:2000']; }
        return $rules;
    }
    public function questionData(): array
    {
        $data = $this->validated(); $data['options'] = [];
        foreach (['a','b','c','d'] as $key) { $data['options'][$key] = $data['option_'.$key]; unset($data['option_'.$key]); }
        return $data;
    }
}
