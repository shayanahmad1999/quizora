<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Attempt,AuditLog,Quiz};
use App\Support\ListFilters;
use Illuminate\Http\Request;

final class ReportController extends Controller
{
    private function query(array $filters)
    {
        $q=Attempt::with('user');
        if (!empty($filters['q'])) {
            $term=$filters['q'];
            $q->where(function ($q) use ($term) { $q->whereHas('user',function ($u) use ($term) { ListFilters::search($u,$term,['name','email']); })->orWhere('quiz_title','like','%'.$term.'%'); });
        }
        foreach (['quiz_id','user_id'] as $field) { if (!empty($filters[$field])) { $q->where($field,$filters[$field]); } }
        if (in_array($filters['status'] ?? '',['in_progress','submitted','expired'])) { $q->where('status',$filters['status']); }
        return $q;
    }
    public function index(Request $request)
    {
        $f=ListFilters::read($request); $q=$this->query($f);
        $completed=(clone $q)->where('status','!=','in_progress');
        return $this->pages->render('admin.reports',['attempts'=>$q->latest()->paginate($f['per_page'] ?? 10)->withQueryString(),'quizzes'=>Quiz::withTrashed()->orderBy('title')->get(),'stats'=>['Attempts'=>(clone $this->query($f))->count(),'Completed'=>(clone $completed)->count(),'Average'=>number_format((float) (clone $completed)->avg('percentage'),1).'%','Passed'=>(clone $completed)->where('passed',true)->count()]],'Performance reports');
    }
    public function export(Request $request)
    {
        $f=ListFilters::read($request);
        return response()->streamDownload(function () use ($f) {
            $out=fopen('php://output','w');
            fputcsv($out,['Attempt','Learner','Email','Quiz','Category','Status','Correct','Total','Percentage','Passed','Started (UTC)','Submitted (UTC)'],',','"','');
            foreach ($this->query($f)->lazyById(500) as $a) {
                $row=[$a->uuid,$a->user?->name,$a->user?->email,$a->quiz_title,$a->category_name,$a->status,$a->correct_count,$a->total_questions,$a->percentage,$a->passed===null ? '' : ($a->passed ? 'Yes':'No'),$a->started_at?->format('Y-m-d H:i:s'),$a->submitted_at?->format('Y-m-d H:i:s')];
                $row=array_map(static function ($value) { $s=(string) $value; return preg_match('/^(?:\s*[=+@\-]|[\t\r\n])/',$s) ? "'".$s : $s; },$row);
                fputcsv($out,$row,',','"','');
            }
            fclose($out);
        },'quizora-results-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function exportSingle(Attempt $attempt)
    {
        return response()->streamDownload(function () use ($attempt) {
            $out=fopen('php://output','w');
            fputcsv($out,['Attempt','Learner','Email','Quiz','Category','Status','Correct','Total','Percentage','Passed','Started (UTC)','Submitted (UTC)'],',','"','');
            $row=[$attempt->uuid,$attempt->user?->name,$attempt->user?->email,$attempt->quiz_title,$attempt->category_name,$attempt->status,$attempt->correct_count,$attempt->total_questions,$attempt->percentage,$attempt->passed===null ? '' : ($attempt->passed ? 'Yes':'No'),$attempt->started_at?->format('Y-m-d H:i:s'),$attempt->submitted_at?->format('Y-m-d H:i:s')];
            $row=array_map(static function ($value) { $s=(string) $value; return preg_match('/^(?:\s*[=+@\-]|[\t\r\n])/',$s) ? "'".$s : $s; },$row);
            fputcsv($out,$row,',','"','');
            
            foreach ($attempt->questions as $q) {
                $detailRow=['', '', '', '', '', 'Question '.$q->sequence, $q->prompt, $q->selected_option, $q->correct_option, $q->selected_option === $q->correct_option ? 'Correct' : 'Incorrect', $q->answered_at?->format('Y-m-d H:i:s'), ''];
                $detailRow=array_map(static function ($value) { $s=(string) $value; return preg_match('/^(?:\s*[=+@\-]|[\t\r\n])/',$s) ? "'".$s : $s; },$detailRow);
                fputcsv($out,$detailRow,',','"','');
            }
            fclose($out);
        },'quizora-attempt-'.$attempt->uuid.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function audit(Request $request)
    {
        $f=ListFilters::read($request); $q=AuditLog::with('user'); ListFilters::search($q,$f['q'] ?? null,['action','entity_type','description']);
        return $this->pages->render('admin.audit',['logs'=>$q->latest('id')->paginate($f['per_page'] ?? 20)->withQueryString()],'Activity log');
    }
}