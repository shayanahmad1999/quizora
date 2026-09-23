<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Attempt extends Model
{

    protected $fillable = ['uuid','quiz_id','user_id','quiz_title','category_name','status','total_questions','pass_percentage','review_answers','correct_count','percentage','passed','started_at','expires_at','submitted_at'];
    protected function casts(): array { return ['started_at'=>'immutable_datetime','expires_at'=>'immutable_datetime','submitted_at'=>'immutable_datetime','review_answers'=>'boolean','passed'=>'boolean','percentage'=>'decimal:2','total_questions'=>'integer','pass_percentage'=>'integer']; }
    public function questions() { return $this->hasMany(AttemptQuestion::class)->orderBy('sequence'); }
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
    public function quiz() { return $this->belongsTo(Quiz::class)->withTrashed(); }
    public function getRouteKeyName(): string { return 'uuid'; }
    public function isComplete(): bool { return $this->status !== 'in_progress'; }
}
