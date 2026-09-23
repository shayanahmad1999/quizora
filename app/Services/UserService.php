<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class UserService
{
    public function __construct(private readonly AuditService $audit) {}
    public function save(array $data, ?User $existing = null): User
    {
        return DB::transaction(function () use ($data, $existing) {
            // Same lock order protects the final active administrator under concurrent edits.
            $admins = User::where('role', 'super_admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user = $existing ? User::whereKey($existing->id)->lockForUpdate()->firstOrFail() : new User;
            $losingAdmin = $user->exists && $user->isAdmin() && $user->is_active && ($data['role'] !== 'super_admin' || !$data['is_active']);
            if ($user->exists && $user->id === auth()->id() && ($losingAdmin || !$data['is_active'])) {
                throw ValidationException::withMessages(['role'=>'You cannot disable or demote your own administrator account.']);
            }
            if ($losingAdmin && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role'=>'At least one active super administrator must remain.']);
            }
            $attributes = Arr::except($data, ['theme_ids']);
            if (empty($attributes['password'])) { unset($attributes['password']); }
            $passwordChanged = isset($attributes['password']);
            if ($user->exists) {
                $attributes['auth_version'] = $user->auth_version + 1;
                if ($passwordChanged) { $attributes['must_change_password'] = true; }
            } else { $attributes['must_change_password'] = true; }
            $attributes['theme_id'] = $attributes['theme_mode'] === 'fixed' ? $attributes['theme_id'] : null;
            $user->fill($attributes)->save();
            $user->themes()->sync($data['theme_ids'] ?? []);
            if ($user->id === auth()->id()) { session()->put('auth_version', $user->auth_version); }
            $this->audit->record($existing ? 'updated' : 'created', $user, 'Account '.$user->email);
            return $user;
        }, 3);
    }
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $admins = User::where('role', 'super_admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->id === auth()->id() || ($user->isAdmin() && $user->is_active && $admins->count() <= 1)) {
                throw ValidationException::withMessages(['account'=>'You cannot delete yourself or the last active super administrator.']);
            }
            $user->forceFill(['is_active'=>false, 'auth_version'=>$user->auth_version + 1])->save();
            $this->audit->record('deleted', $user, 'Account '.$user->email.' (history retained)');
            $user->delete();
        }, 3);
    }
}
