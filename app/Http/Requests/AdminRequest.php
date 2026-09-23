<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() === true; }
    public function messages(): array { return ['required'=>'Please complete :attribute.', 'unique'=>'This :attribute is already in use.', 'exists'=>'The selected :attribute is unavailable.', 'in'=>'Choose a valid :attribute.']; }
}
