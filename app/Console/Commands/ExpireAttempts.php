<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Models\Attempt;
use App\Services\QuizAttemptService;
use Illuminate\Console\Command;
final class ExpireAttempts extends Command
{
    protected $signature = 'quizora:expire';
    protected $description = 'Finalize expired attempts using their last saved answers.';
    public function handle(QuizAttemptService $service): int
    {
        $count=0;
        foreach (Attempt::where('status','in_progress')->where('expires_at','<=',now())->lazyById(200) as $attempt) { $service->finalize($attempt); $count++; }
        $this->info("Finalized {$count} expired attempts."); return self::SUCCESS;
    }
}
