<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
final class AuditService
{
    public function record(string $action, Model $entity, string $description): void
    {
        AuditLog::create(['user_id'=>auth()->id(), 'action'=>$action, 'entity_type'=>class_basename($entity), 'entity_id'=>$entity->getKey(), 'description'=>mb_substr($description, 0, 255), 'created_at'=>now()]);
    }
}
