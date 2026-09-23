<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Setting extends Model
{

    protected $fillable = ['site_name','tagline','support_email','default_theme_id'];
    protected function casts(): array { return []; }

}
