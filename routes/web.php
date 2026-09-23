<?php
use App\Http\Controllers\{AuthController,ProfileController,DashboardController,LearnerQuizController,AttemptController};
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;
Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'create'])->name('login');
    Route::post('/login',[AuthController::class,'store'])->middleware('throttle:30,1')->name('login.store');
});
Route::middleware(['auth','active'])->group(function () {
    Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
    Route::get('/dashboard',DashboardController::class)->name('dashboard');
    Route::get('/profile',[ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',[ProfileController::class,'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::get('/quizzes',[LearnerQuizController::class,'index'])->name('quizzes.index');
    Route::post('/quizzes/{quiz}/start',[LearnerQuizController::class,'start'])->middleware('throttle:20,1')->name('quizzes.start');
    Route::get('/attempts',[AttemptController::class,'index'])->name('attempts.index');
    Route::get('/attempts/{attempt}',[AttemptController::class,'show'])->name('attempts.show');
    Route::patch('/attempts/{attempt}/answers/{question}',[AttemptController::class,'answer'])->whereNumber('question')->middleware('throttle:180,1')->name('attempts.answer');
    Route::post('/attempts/{attempt}/submit',[AttemptController::class,'submit'])->middleware('throttle:20,1')->name('attempts.submit');
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::resource('categories',Admin\CategoryController::class)->except('show');
        Route::resource('questions',Admin\QuestionController::class)->except('show');
        Route::resource('quizzes',Admin\QuizController::class)->except('show');
        Route::resource('users',Admin\UserController::class)->except('show');
        Route::resource('themes',Admin\ThemeController::class)->except('show');
        Route::get('/reports', [Admin\ReportController::class,'index'])->name('reports.index');
        Route::get('/reports/export', [Admin\ReportController::class,'export'])->name('reports.export');
        Route::get('/reports/{attempt}/export', [Admin\ReportController::class,'exportSingle'])->name('reports.exportSingle');
        Route::get('/audit', [Admin\ReportController::class,'audit'])->name('audit.index');
        Route::get('/settings', [Admin\SettingsController::class,'edit'])->name('settings.edit');
        Route::patch('/settings', [Admin\SettingsController::class,'update'])->name('settings.update');
    });
});
