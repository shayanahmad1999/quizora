<?php
declare(strict_types=1);
namespace App\Http\Requests;
use Illuminate\Validation\Rule;
final class SettingsRequest extends AdminRequest
{
    public function rules(): array { return ['site_name'=>['required','string','max:80'], 'tagline'=>['required','string','max:180'], 'support_email'=>['nullable','email','max:190'], 'default_theme_id'=>['required','integer',Rule::exists('themes','id')->where('is_active',true)]]; }
}
