<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Models\{Category,Question};
use App\Services\AuditService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
final class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $f = ListFilters::read($request); $q = Question::with('category');
        ListFilters::search($q,$f['q'] ?? null,['prompt']);
        if (!empty($f['category_id'])) { $q->where('category_id',$f['category_id']); }
        if (!empty($f['difficulty'])) { $q->where('difficulty',$f['difficulty']); }
        if (in_array($f['status'] ?? '', ['active','inactive'])) { $q->where('is_active',$f['status']==='active'); }
        return $this->pages->render('admin.index',['resource'=>'questions','records'=>$q->latest()->paginate($f['per_page'] ?? 10)->withQueryString(),'categories'=>Category::orderBy('name')->get()], 'Question bank');
    }
    public function create() { return $this->form(new Question(['is_active'=>true,'difficulty'=>'medium','options'=>[]])); }
    public function edit(Question $question) { return $this->form($question); }
    private function form(Question $record) { return $this->pages->render('admin.form',['resource'=>'questions','record'=>$record,'categories'=>Category::orderBy('name')->pluck('name','id')],$record->exists ? 'Edit question' : 'New question'); }
    public function store(QuestionRequest $request, AuditService $audit)
    {
        DB::transaction(function () use ($request,$audit) { $record=Question::create($request->questionData()); $audit->record('created',$record,'Question added to the bank'); });
        return $this->pages->saved(route('admin.questions.index'),'Question created.');
    }
    public function update(QuestionRequest $request, Question $question, AuditService $audit)
    {
        DB::transaction(function () use ($request,$question,$audit) { $question->update($request->questionData()); $audit->record('updated',$question,'Question updated; existing attempts unchanged'); });
        return $this->pages->saved(route('admin.questions.index'),'Question updated. Existing attempts are unchanged.');
    }
    public function destroy(Question $question, AuditService $audit)
    {
        DB::transaction(function () use ($question,$audit) { $audit->record('deleted',$question,'Question removed from bank'); $question->delete(); });
        return $this->pages->saved(route('admin.questions.index'),'Question deleted. Past attempt snapshots remain available.');
    }
}
