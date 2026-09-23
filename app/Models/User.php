<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
final class User extends Authenticatable
{
    use SoftDeletes, Notifiable;
    protected $fillable = ['name','email','password','role','is_active','must_change_password','auth_version','theme_mode','theme_id','last_theme_id','last_login_at'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed','is_active'=>'boolean','must_change_password'=>'boolean','last_login_at'=>'datetime','auth_version'=>'integer']; }
    public function isAdmin(): bool { return $this->role === 'super_admin'; }
    public function themes() { return $this->belongsToMany(Theme::class); }
    public function theme() { return $this->belongsTo(Theme::class); }
    public function attempts() { return $this->hasMany(Attempt::class); }
    public function quizzes() { return $this->belongsToMany(Quiz::class); }
}
