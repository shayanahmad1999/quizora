<?php
declare(strict_types=1);
namespace App\Support;
use App\Models\Setting;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
final class PageResponse
{
    public function __construct(private readonly ThemeService $themes) {}
    public function render(string $view, array $data = [], string $title = 'Dashboard'): Response|JsonResponse
    {
        $settings = Setting::first() ?? new Setting(['site_name'=>'Quizora','tagline'=>'A little practice. A lot of possibility.']);
        $theme = $this->themes->resolve(request()->user());
        $data = array_merge($data, compact('settings','theme','title'));
        $content = view($view, $data)->render();
        $shellData = array_merge($data, ['content'=>$content]);
        if (request()->expectsJson()) {
            return response()->json(['url'=>request()->fullUrl(), 'html'=>view('layouts.shell', $shellData)->render(), 'title'=>$title.' | '.$settings->site_name, 'theme'=>$theme, 'csrf_token'=>csrf_token()]);
        }
        return response(view('layouts.app', $shellData));
    }
    public function saved(string $url, string $message = 'Changes saved.'): JsonResponse|RedirectResponse
    {
        return request()->expectsJson() ? response()->json(['redirect'=>$url, 'message'=>$message, 'csrf_token'=>csrf_token()]) : redirect($url)->with('status', $message);
    }
}
