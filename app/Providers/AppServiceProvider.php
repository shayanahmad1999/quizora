<?php
declare(strict_types=1);
namespace App\Providers;
use App\Models\Attempt;
use App\Policies\AttemptPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        Gate::policy(Attempt::class, AttemptPolicy::class);
        Gate::define('administer', fn ($user) => $user->isAdmin() && $user->is_active);
    }
}
