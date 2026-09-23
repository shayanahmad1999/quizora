<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\{Attempt,Category,Question,Quiz,User};
use Illuminate\Http\Request;
final class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $stats = ['Learners'=>User::where('role','learner')->count(), 'Questions'=>Question::count(), 'Published quizzes'=>Quiz::where('is_published',true)->count(), 'Completed attempts'=>Attempt::where('status','!=','in_progress')->count()];
            return $this->pages->render('dashboard.admin', ['stats'=>$stats, 'categories'=>Category::withCount('questions')->orderBy('name')->get(), 'recent'=>Attempt::with('user')->latest()->limit(6)->get()], 'Overview');
        }
        $user = $request->user();
        $completed = $user->attempts()->where('status','!=','in_progress');
        $stats = ['Completed'=>(clone $completed)->count(), 'Average score'=>number_format((float) (clone $completed)->avg('percentage'), 1).'%', 'Passed'=>(clone $completed)->where('passed',true)->count()];
        $available = Quiz::where('is_published',true)->whereHas('category',fn($q)=>$q->where('is_active',true)->whereNull('deleted_at'))
            ->where(fn($q)=>$q->where('access_mode','all')->orWhereHas('users',fn($u)=>$u->where('users.id',$user->id)));
        return $this->pages->render('dashboard.learner', ['stats'=>$stats, 'quizzes'=>$available->with('category')->latest()->limit(3)->get(), 'recent'=>$user->attempts()->latest()->limit(5)->get(), 'inProgress'=>$user->attempts()->where('status','in_progress')->latest()->get()], 'My learning');
    }
}
