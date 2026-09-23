<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Question extends Model
{
    use SoftDeletes;
    protected $fillable = ['category_id','prompt','options','correct_option','explanation','difficulty','is_active'];
    protected function casts(): array { return ['options'=>'array','is_active'=>'boolean']; }
    protected $hidden = ['correct_option','explanation'];
    public function category() { return $this->belongsTo(Category::class)->withTrashed(); }
}
