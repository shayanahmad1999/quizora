<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Collection;
final class ThemeService
{
    private function pool(User $user): Collection
    {
        $assigned = $user->themes()->where('is_active', true)->get();
        return $assigned->isNotEmpty() ? $assigned : Theme::where('is_active', true)->get();
    }
    public function onLogin(User $user): void
    {
        if ($user->theme_mode === 'fixed') { session()->forget('theme_id'); return; }
        $pool = $this->pool($user);
        $eligible = $pool->count() > 1 ? $pool->where('id', '!=', $user->last_theme_id) : $pool;
        $theme = $eligible->isNotEmpty() ? $eligible->random() : null;
        session()->put('theme_id', $theme?->id);
        $user->forceFill(['last_theme_id'=>$theme?->id])->save();
    }
    public function resolve(?User $user): array
    {
        $theme = null;
        if ($user?->theme_mode === 'fixed') {
            $theme = Theme::whereKey($user->theme_id)->where('is_active', true)->first();
        } elseif ($user) {
            $pool = $this->pool($user);
            $theme = $pool->firstWhere('id', session('theme_id'));
            if (!$theme) { $this->onLogin($user); $theme = $pool->firstWhere('id', session('theme_id')); }
        }
        $theme ??= Theme::whereKey(Setting::value('default_theme_id'))->where('is_active', true)->first();
        $theme ??= Theme::where('is_active', true)->first();
        return $theme ? $theme->only(['name','layout','accent','background','surface','text','is_dark']) : [
            'name'=>'Ocean', 'layout'=>'sidebar', 'accent'=>'#087F8C', 'background'=>'#F3F6F8', 'surface'=>'#FFFFFF', 'text'=>'#192D3D', 'is_dark'=>false,
        ];
    }
}
