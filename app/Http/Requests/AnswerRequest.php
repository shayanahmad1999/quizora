<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
final class AnswerRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('answer', $this->route('attempt')); }
    public function rules(): array { return ['selected_option'=>['nullable','in:a,b,c,d']]; }
}
