<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class AuditLog extends Model
{

    protected $fillable = ['user_id','action','entity_type','entity_id','description','created_at'];
    protected function casts(): array { return ['created_at'=>'immutable_datetime']; }
    public $timestamps = false;
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
}
