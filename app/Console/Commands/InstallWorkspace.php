<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
final class InstallWorkspace extends Command
{
    protected $signature = 'quizora:install {--demo : Add non-production sample questions and quizzes} {--skip-admin : Do not prompt for an administrator}';
    protected $description = 'Initialize a local workspace without dropping tables or overwriting existing records.';
    public function handle(): int
    {
        if (app()->environment('production')) { $this->error('Use docs/DEPLOYMENT.md for production deployment.'); return self::FAILURE; }
        if (!config('app.key') && $this->call('key:generate') !== 0) { return self::FAILURE; }
        if ($this->call('migrate', ['--force'=>true]) !== 0) { return self::FAILURE; }
        if ($this->call('db:seed', ['--force'=>true]) !== 0) { return self::FAILURE; }
        if ($this->option('demo') && $this->call('db:seed',['--class'=>\Database\Seeders\DemoContentSeeder::class,'--force'=>true]) !== 0) { return self::FAILURE; }
        if (!$this->option('skip-admin') && !User::where('role','super_admin')->where('is_active',true)->exists()) {
            if ($this->call('quizora:admin') !== 0) { return self::FAILURE; }
        }
        $this->newLine(); $this->info('Workspace ready. Start the web server with: php artisan serve');
        $this->line('For automatic expiry while all browsers are closed: php artisan schedule:work');
        return self::SUCCESS;
    }
}
