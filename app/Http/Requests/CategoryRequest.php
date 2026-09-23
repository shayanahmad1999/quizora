<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class CategoryRequest extends AdminRequest
{
    public function rules(): array { return [
        'name'=>['required','string','max:100',Rule::unique('categories')->ignore($this->route('category'))],
        'description'=>['nullable','string','max:2000'], 'color'=>['required','regex:/^#[0-9a-fA-F]{6}$/'], 'is_active'=>['required','boolean'],
    ]; }
}
