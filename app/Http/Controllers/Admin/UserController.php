<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\{Theme,User};
use App\Services\UserService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
final class UserController extends Controller
{
    public function index(Request $request)
    {
        $f=ListFilters::read($request); $q=User::with('theme')->withCount('attempts');
        ListFilters::search($q,$f['q'] ?? null,['name','email']);
        if (!empty($f['role'])) { $q->where('role',$f['role']); }
        if (in_array($f['status'] ?? '', ['active','inactive'])) { $q->where('is_active',$f['status']==='active'); }
        return $this->pages->render('admin.index',['resource'=>'users','records'=>$q->latest()->paginate($f['per_page'] ?? 10)->withQueryString()], 'People');
    }
    public function create() { return $this->form(new User(['role'=>'learner','is_active'=>true,'theme_mode'=>'random_login'])); }
    public function edit(User $user) { return $this->form($user->load('themes')); }
    private function form(User $record) { return $this->pages->render('admin.form',['resource'=>'users','record'=>$record,'themes'=>Theme::where('is_active',true)->orderBy('name')->get()],$record->exists ? 'Edit account' : 'New account'); }
    public function store(UserRequest $request, UserService $service) { $service->save($request->validated()); return $this->pages->saved(route('admin.users.index'),'Account created. Share the temporary password privately.'); }
    public function update(UserRequest $request, User $user, UserService $service) { $service->save($request->validated(),$user); return $this->pages->saved(route('admin.users.index'),'Account updated. Existing sessions must sign in again.'); }
    public function destroy(User $user, UserService $service) { $service->delete($user); return $this->pages->saved(route('admin.users.index'),'Account deleted. Results are retained.'); }
}
