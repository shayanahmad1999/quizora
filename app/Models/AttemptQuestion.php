<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class AttemptQuestion extends Model
{

    protected $fillable = ['attempt_id','question_id','sequence','prompt','options','correct_option','explanation','selected_option','answered_at'];
    protected function casts(): array { return ['options'=>'array','answered_at'=>'immutable_datetime','sequence'=>'integer']; }
    protected $hidden = ['correct_option','explanation'];
    public function attempt() { return $this->belongsTo(Attempt::class); }
}
