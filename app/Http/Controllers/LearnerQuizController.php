<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\{Category,Quiz};
use App\Services\QuizAttemptService;
use App\Support\ListFilters;
use Illuminate\Http\Request;
final class LearnerQuizController extends Controller
{
    public function index(Request $request)
    {
        $f = ListFilters::read($request); $user = $request->user();
        $query = Quiz::with('category')->where('is_published',true)
            ->whereHas('category',fn($q)=>$q->where('is_active',true)->whereNull('deleted_at'))
            ->where(fn($q)=>$q->where('access_mode','all')->orWhereHas('users',fn($u)=>$u->where('users.id',$user->id)))
            ->withCount(['attempts'=>fn($q)=>$q->where('user_id',$user->id)]);
        ListFilters::search($query, $f['q'] ?? null, ['title','description']);
        if (!empty($f['category_id'])) { $query->where('category_id',$f['category_id']); }
        return $this->pages->render('quizzes.index', ['quizzes'=>$query->latest()->paginate($f['per_page'] ?? 10)->withQueryString(), 'categories'=>Category::where('is_active',true)->orderBy('name')->get()], 'Explore quizzes');
    }
    public function start(Request $request, Quiz $quiz, QuizAttemptService $service)
    {
        abort_if($request->user()->isAdmin(), 403, 'Use a learner account to take quizzes.');
        $attempt = $service->start($request->user(), $quiz);
        return $this->pages->saved(route('attempts.show',$attempt), $attempt->isComplete() ? 'This attempt has ended.' : 'Your quiz is ready.');
    }
}
