<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\AuditService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $f = ListFilters::read($request); $q = Category::withCount('questions');
        ListFilters::search($q,$f['q'] ?? null,['name','description']);
        if (in_array($f['status'] ?? '', ['active','inactive'])) { $q->where('is_active',$f['status']==='active'); }
        return $this->pages->render('admin.index',['resource'=>'categories','records'=>$q->latest()->paginate($f['per_page'] ?? 10)->withQueryString()], 'Categories');
    }
    public function create() { return $this->form(new Category(['is_active'=>true,'color'=>'#087F8C'])); }
    public function edit(Category $category) { return $this->form($category); }
    private function form(Category $record) { return $this->pages->render('admin.form', ['resource'=>'categories','record'=>$record], $record->exists ? 'Edit category' : 'New category'); }
    public function store(CategoryRequest $request, AuditService $audit)
    {
        DB::transaction(function () use ($request,$audit) { $record = Category::create($request->validated()); $audit->record('created',$record,'Category '.$record->name); });
        return $this->pages->saved(route('admin.categories.index'),'Category created.');
    }
    public function update(CategoryRequest $request, Category $category, AuditService $audit)
    {
        DB::transaction(function () use ($request,$category,$audit) { $category->update($request->validated()); $audit->record('updated',$category,'Category '.$category->name); });
        return $this->pages->saved(route('admin.categories.index'),'Category updated.');
    }
    public function destroy(Category $category, AuditService $audit)
    {
        DB::transaction(function () use ($category,$audit) {
            if ($category->questions()->exists() || $category->quizzes()->exists()) { throw ValidationException::withMessages(['category'=>'This category contains questions or quizzes. Set it to inactive instead, or move/delete its contents first.']); }
            $audit->record('deleted',$category,'Category '.$category->name); $category->delete();
        });
        return $this->pages->saved(route('admin.categories.index'),'Category deleted.');
    }
}
