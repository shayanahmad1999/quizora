<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
final class ProfileController extends Controller
{
    public function edit(Request $request) { return $this->pages->render('profile.edit', ['user'=>$request->user()], 'My account'); }
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'=>['required','string','max:100'], 'current_password'=>['required','current_password'],
            'password'=>[$user->must_change_password ? 'required' : 'nullable','string','confirmed','different:current_password','max:128',Password::min(12)->mixedCase()->numbers()],
        ]);
        DB::transaction(function () use ($data, $user, $request) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!$user->is_active || !Hash::check($data['current_password'], $user->password) || $user->auth_version !== (int) $request->session()->get('auth_version', $user->auth_version)) {
                throw ValidationException::withMessages(['current_password'=>'Your account changed. Sign in again before updating your password.']);
            }
            $user->name = $data['name'];
            if (!empty($data['password'])) {
                $user->password = $data['password']; $user->must_change_password = false; $user->auth_version++;
            }
            $user->save();
            $request->session()->put('auth_version', $user->auth_version);
        });
        $request->session()->regenerate(); $request->session()->regenerateToken();
        return $this->pages->saved(route('dashboard'), 'Your account has been updated.');
    }
}
