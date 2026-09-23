<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
final class AuthController extends Controller
{
    public function create() { return $this->pages->render('auth.login', [], 'Sign in'); }
    public function store(Request $request, ThemeService $themes)
    {
        $data = $request->validate(['email'=>['required','email','max:190'],'password'=>['required','string','max:128']]);
        $email = mb_strtolower(trim($data['email']));
        $key = 'login:'.hash('sha256', $email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message'=>'Too many sign-in attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.'], 429)->header('Retry-After', (string) RateLimiter::availableIn($key));
        }
        if (!Auth::attempt(['email'=>$email, 'password'=>$data['password'], 'is_active'=>true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email'=>'The email or password is incorrect, or the account is inactive.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $user = $request->user();
        $request->session()->put('auth_version', $user->auth_version);
        $user->update(['last_login_at'=>now()]);
        $themes->onLogin($user);
        return $this->pages->saved(route($user->must_change_password ? 'profile.edit' : 'dashboard'), 'Welcome, '.$user->name.'.');
    }
    public function destroy(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return $this->pages->saved(route('login'), 'You have been signed out.');
    }
}
