<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Quiz extends Model
{
    use SoftDeletes;
    protected $fillable = ['category_id','title','description','question_count','duration_minutes','pass_percentage','max_attempts','shuffle_questions','shuffle_options','review_answers','access_mode','is_published'];
    protected function casts(): array { return ['question_count'=>'integer','duration_minutes'=>'integer','pass_percentage'=>'integer','max_attempts'=>'integer','shuffle_questions'=>'boolean','shuffle_options'=>'boolean','review_answers'=>'boolean','is_published'=>'boolean']; }
    public function category() { return $this->belongsTo(Category::class)->withTrashed(); }
    public function users() { return $this->belongsToMany(User::class); }
    public function attempts() { return $this->hasMany(Attempt::class); }
    public function availableTo(User $user): bool { return $this->access_mode === 'all' || $this->users()->whereKey($user->id)->exists(); }
}
