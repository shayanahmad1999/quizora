<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\{Setting,Theme};
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
final class SettingsController extends Controller
{
    public function edit() { return $this->pages->render('admin.settings',['record'=>Setting::firstOrFail(),'themes'=>Theme::where('is_active',true)->pluck('name','id')],'Workspace settings'); }
    public function update(SettingsRequest $request, AuditService $audit)
    {
        DB::transaction(function () use ($request,$audit) { $record=Setting::firstOrFail(); $record->update($request->validated()); $audit->record('updated',$record,'Workspace settings updated'); });
        return $this->pages->saved(route('admin.settings.edit'),'Workspace settings saved.');
    }
}
