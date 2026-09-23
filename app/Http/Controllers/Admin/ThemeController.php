<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\ThemeRequest;
use App\Models\{Setting,Theme,User};
use App\Services\AuditService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class ThemeController extends Controller
{
    public function index(Request $request)
    {
        $f=ListFilters::read($request); $q=Theme::query(); ListFilters::search($q,$f['q'] ?? null,['name']);
        if (in_array($f['status'] ?? '', ['active','inactive'])) { $q->where('is_active',$f['status']==='active'); }
        return $this->pages->render('admin.index',['resource'=>'themes','records'=>$q->orderBy('id')->paginate($f['per_page'] ?? 10)->withQueryString()], 'Appearance');
    }
    public function create() { return $this->form(new Theme(['layout'=>'sidebar','accent'=>'#087F8C','background'=>'#F3F6F8','surface'=>'#FFFFFF','text'=>'#192D3D','is_dark'=>false,'is_active'=>true])); }
    public function edit(Theme $theme) { return $this->form($theme); }
    private function form(Theme $record) { return $this->pages->render('admin.form',['resource'=>'themes','record'=>$record],$record->exists ? 'Edit theme' : 'New theme'); }
    private function ensureRemovable(Theme $theme): void
    {
        $active=Theme::where('is_active',true)->orderBy('id')->lockForUpdate()->get();
        if (($theme->is_active && $active->count() <= 1) || Setting::where('default_theme_id',$theme->id)->exists() || User::where('theme_id',$theme->id)->exists()) {
            throw ValidationException::withMessages(['theme'=>'Keep at least one active theme. Reassign fixed users and the default theme before disabling or deleting this theme.']);
        }
    }
    public function store(ThemeRequest $request, AuditService $audit)
    {
        DB::transaction(function () use ($request,$audit) { $record=Theme::create($request->validated()); $audit->record('created',$record,'Theme '.$record->name); });
        return $this->pages->saved(route('admin.themes.index'),'Theme created.');
    }
    public function update(ThemeRequest $request, Theme $theme, AuditService $audit)
    {
        DB::transaction(function () use ($request,$theme,$audit) { if (!$request->boolean('is_active')) { $this->ensureRemovable($theme); } $theme->update($request->validated()); $audit->record('updated',$theme,'Theme '.$theme->name); });
        return $this->pages->saved(route('admin.themes.index'),'Theme updated.');
    }
    public function destroy(Theme $theme, AuditService $audit)
    {
        DB::transaction(function () use ($theme,$audit) { $this->ensureRemovable($theme); $audit->record('deleted',$theme,'Theme '.$theme->name); $theme->delete(); });
        return $this->pages->saved(route('admin.themes.index'),'Theme deleted.');
    }
}
