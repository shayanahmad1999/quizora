<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class ThemeRequest extends AdminRequest
{
    public function rules(): array
    {
        $rules = ['name'=>['required','string','max:80',Rule::unique('themes')->ignore($this->route('theme'))],
            'layout'=>['required','in:sidebar,topbar,focus'], 'is_dark'=>['required','boolean'], 'is_active'=>['required','boolean']];
        foreach (['accent','background','surface','text'] as $key) { $rules[$key] = ['required','regex:/^#[0-9a-fA-F]{6}$/']; }
        return $rules;
    }
}
