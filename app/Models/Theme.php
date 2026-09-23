<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Theme extends Model
{

    protected $fillable = ['name','layout','accent','background','surface','text','is_dark','is_active'];
    protected function casts(): array { return ['is_dark'=>'boolean','is_active'=>'boolean']; }

}
