<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\{Category,Quiz,User};
use App\Services\AuditService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
final class QuizController extends Controller
{
    public function index(Request $request)
    {
        $f=ListFilters::read($request); $q=Quiz::with('category')->withCount('attempts');
        ListFilters::search($q,$f['q'] ?? null,['title','description']);
        if (!empty($f['category_id'])) { $q->where('category_id',$f['category_id']); }
        if (in_array($f['status'] ?? '', ['published','draft'])) { $q->where('is_published',$f['status']==='published'); }
        return $this->pages->render('admin.index',['resource'=>'quizzes','records'=>$q->latest()->paginate($f['per_page'] ?? 10)->withQueryString(),'categories'=>Category::orderBy('name')->get()], 'Quiz studio');
    }
    public function create() { return $this->form(new Quiz(['question_count'=>5,'duration_minutes'=>10,'pass_percentage'=>60,'max_attempts'=>3,'shuffle_questions'=>true,'shuffle_options'=>true,'review_answers'=>true,'access_mode'=>'all','is_published'=>false])); }
    public function edit(Quiz $quiz) { return $this->form($quiz->load('users')); }
    private function form(Quiz $record)
    {
        return $this->pages->render('admin.form',['resource'=>'quizzes','record'=>$record,'categories'=>Category::orderBy('name')->pluck('name','id'),'learners'=>User::where('role','learner')->orderBy('name')->get()],$record->exists ? 'Edit quiz' : 'New quiz');
    }
    private function save(QuizRequest $request, Quiz $record, AuditService $audit): void
    {
        DB::transaction(function () use ($request,$record,$audit) {
            $data=$request->validated(); $action=$record->exists ? 'updated' : 'created';
            $record->fill(Arr::except($data,['user_ids']))->save();
            $record->users()->sync($data['access_mode']==='assigned' ? ($data['user_ids'] ?? []) : []);
            $audit->record($action,$record,'Quiz '.$record->title);
        });
    }
    public function store(QuizRequest $request, AuditService $audit) { $this->save($request,new Quiz,$audit); return $this->pages->saved(route('admin.quizzes.index'),'Quiz created.'); }
    public function update(QuizRequest $request, Quiz $quiz, AuditService $audit) { $this->save($request,$quiz,$audit); return $this->pages->saved(route('admin.quizzes.index'),'Quiz updated. Existing attempts retain their original settings.'); }
    public function destroy(Quiz $quiz, AuditService $audit)
    {
        DB::transaction(function () use ($quiz,$audit) { $audit->record('deleted',$quiz,'Quiz '.$quiz->title); $quiz->delete(); });
        return $this->pages->saved(route('admin.quizzes.index'),'Quiz deleted. Attempt history is retained.');
    }
}
