<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Category extends Model
{
    use SoftDeletes;
    protected $fillable = ['name','description','color','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function questions() { return $this->hasMany(Question::class); }
    public function quizzes() { return $this->hasMany(Quiz::class); }
}
