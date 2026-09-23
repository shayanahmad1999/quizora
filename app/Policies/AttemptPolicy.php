<?php
declare(strict_types=1);
namespace App\Policies;
use App\Models\Attempt;
use App\Models\User;
final class AttemptPolicy
{
    public function view(User $user, Attempt $attempt): bool { return $user->isAdmin() || (int) $attempt->user_id === (int) $user->id; }
    public function answer(User $user, Attempt $attempt): bool { return !$user->isAdmin() && (int) $attempt->user_id === (int) $user->id; }
}
