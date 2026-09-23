<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
final class UserRequest extends AdminRequest
{
    protected function prepareForValidation(): void { $this->merge(['email'=>mb_strtolower(trim((string) $this->input('email')))]); }
    public function rules(): array { return [
        'name'=>['required','string','max:100'], 'email'=>['required','email','max:190',Rule::unique('users')->ignore($this->route('user'))],
        'password'=>[$this->route('user') ? 'nullable' : 'required','string',Password::min(12)->mixedCase()->numbers(),'max:128'],
        'role'=>['required','in:learner,super_admin'], 'is_active'=>['required','boolean'], 'theme_mode'=>['required','in:fixed,random_login'],
        'theme_id'=>['nullable','required_if:theme_mode,fixed','integer',Rule::exists('themes','id')->where('is_active',true)],
        'theme_ids'=>['sometimes','array','max:100'], 'theme_ids.*'=>['integer','distinct',Rule::exists('themes','id')->where('is_active',true)],
    ]; }
}
