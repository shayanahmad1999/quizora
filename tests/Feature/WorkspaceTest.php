<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\{Attempt,AuditLog,Category,Question,Quiz,Setting,Theme,User};
use App\Services\{QuizAttemptService,ThemeService};
use Database\Seeders\{DatabaseSeeder,DemoContentSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
final class WorkspaceTest extends TestCase
{
    use RefreshDatabase;
    private const PASSWORD='TestOnly-Password123!';
    protected function setUp(): void { parent::setUp(); $this->seed(DatabaseSeeder::class); }
    private function person(array $attributes=[]): User
    {
        return User::create(array_merge(['name'=>'Test Learner','email'=>uniqid('learner',true).'@example.test','password'=>self::PASSWORD,'role'=>'learner','is_active'=>true,'must_change_password'=>false,'theme_mode'=>'random_login'], $attributes));
    }
    private function administrator(): User { return $this->person(['name'=>'Test Administrator','role'=>'super_admin']); }
    private function quiz(array $attributes=[]): Quiz
    {
        $category=Category::create(['name'=>uniqid('Subject'),'is_active'=>true,'color'=>'#087F8C']);
        foreach (range(1,3) as $i) { Question::create(['category_id'=>$category->id,'prompt'=>'Snapshot question '.$i,'options'=>['a'=>'Choice one','b'=>'Choice two','c'=>'Choice three','d'=>'Choice four'],'correct_option'=>'a','explanation'=>'SERVER-ONLY-EXPLANATION-'.$i,'difficulty'=>'easy','is_active'=>true]); }
        return Quiz::create(array_merge(['category_id'=>$category->id,'title'=>'A focused test','question_count'=>3,'duration_minutes'=>10,'pass_percentage'=>60,'max_attempts'=>2,'shuffle_questions'=>false,'shuffle_options'=>false,'review_answers'=>true,'access_mode'=>'all','is_published'=>true],$attributes));
    }
    private function attempt(?User $learner=null, ?Quiz $quiz=null): Attempt { return app(QuizAttemptService::class)->start($learner ?? $this->person(),$quiz ?? $this->quiz()); }
    public function test_guests_cannot_read_admin_routes(): void { $this->getJson('/admin/users')->assertUnauthorized(); }
    public function test_learners_cannot_read_admin_routes(): void { $this->actingAs($this->person())->getJson('/admin/questions')->assertForbidden(); }
    public function test_there_is_no_public_registration_route(): void { $this->get('/register')->assertNotFound(); $this->post('/register',[])->assertNotFound(); }
    public function test_administrator_can_render_every_management_screen(): void
    {
        $this->actingAs($this->administrator());
        foreach (['/dashboard','/admin/categories','/admin/questions','/admin/quizzes','/admin/users','/admin/themes','/admin/reports','/admin/audit','/admin/settings','/admin/categories/create','/admin/questions/create','/admin/quizzes/create','/admin/users/create','/admin/themes/create'] as $url) { $this->getJson($url)->assertOk()->assertJsonStructure(['html','theme','csrf_token','title']); }
    }
    public function test_user_account_creation_hashes_password_and_requires_first_login_change(): void
    {
        $this->actingAs($this->administrator())->postJson('/admin/users',['name'=>'New Learner','email'=>'New@Example.test','password'=>self::PASSWORD,'role'=>'learner','is_active'=>true,'theme_mode'=>'random_login'])->assertOk();
        $user=User::where('email','new@example.test')->firstOrFail();
        self::assertTrue(Hash::check(self::PASSWORD,$user->password)); self::assertTrue($user->must_change_password);
    }
    public function test_temporary_password_restricts_access_until_replaced(): void
    {
        $user=$this->person(['must_change_password'=>true]);
        $this->actingAs($user)->getJson('/dashboard')->assertStatus(409)->assertJsonPath('redirect',route('profile.edit'));
        $this->patchJson('/profile',['name'=>'Updated Learner','current_password'=>self::PASSWORD,'password'=>'A-New-Password456!','password_confirmation'=>'A-New-Password456!'])->assertOk();
        self::assertFalse($user->fresh()->must_change_password); self::assertTrue(Hash::check('A-New-Password456!',$user->fresh()->password));
    }
    public function test_inactive_account_is_rejected_on_next_request(): void
    {
        $user=$this->person(); $this->actingAs($user); $user->is_active=false; $user->save();
        $this->getJson('/dashboard')->assertUnauthorized();
    }
    public function test_changed_session_version_is_rejected(): void
    {
        $user=$this->person(); $this->actingAs($user)->withSession(['auth_version'=>1]); $user->auth_version=2; $user->save();
        $this->getJson('/dashboard')->assertUnauthorized();
    }
    public function test_login_is_throttled_after_five_failures(): void
    {
        $user=$this->person();
        for($i=0;$i<5;$i++) { $this->postJson('/login',['email'=>$user->email,'password'=>'wrong'])->assertUnprocessable(); }
        $this->postJson('/login',['email'=>$user->email,'password'=>'wrong'])->assertStatus(429);
    }
    public function test_administrator_cannot_demote_or_delete_self(): void
    {
        $admin=$this->administrator(); $this->actingAs($admin);
        $this->patchJson('/admin/users/'.$admin->id,['name'=>$admin->name,'email'=>$admin->email,'role'=>'learner','is_active'=>true,'theme_mode'=>'random_login'])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->deleteJson('/admin/users/'.$admin->id)->assertUnprocessable();
    }
    public function test_theme_colors_reject_css_injection(): void
    {
        $this->actingAs($this->administrator())->postJson('/admin/themes',['name'=>'Unsafe','layout'=>'sidebar','accent'=>'red;position:fixed','background'=>'#FFFFFF','surface'=>'#FFFFFF','text'=>'#192D3D','is_dark'=>false,'is_active'=>true])->assertUnprocessable()->assertJsonValidationErrors('accent');
    }
    public function test_random_login_uses_assigned_pool_without_immediate_repeat(): void
    {
        $themes=Theme::orderBy('id')->take(2)->get(); $user=$this->person(['last_theme_id'=>$themes[0]->id]);
        $user->themes()->sync($themes->modelKeys()); app(ThemeService::class)->onLogin($user);
        self::assertSame($themes[1]->id,session('theme_id'));
        $resolved=app(ThemeService::class)->resolve($user); self::assertSame($themes[1]->name,$resolved['name']);
    }
    public function test_fixed_theme_does_not_change_on_login(): void
    {
        $theme=Theme::where('name','Meadow')->firstOrFail(); $user=$this->person(['theme_mode'=>'fixed','theme_id'=>$theme->id]);
        $service=app(ThemeService::class); $service->onLogin($user); self::assertSame('Meadow',$service->resolve($user)['name']);
        $service->onLogin($user); self::assertSame('Meadow',$service->resolve($user)['name']);
    }
    public function test_default_theme_cannot_be_deleted(): void
    {
        $this->actingAs($this->administrator())->deleteJson('/admin/themes/'.Setting::value('default_theme_id'))->assertUnprocessable();
    }
    public function test_start_is_idempotent_while_an_attempt_is_active(): void
    {
        $user=$this->person(); $quiz=$this->quiz(); $service=app(QuizAttemptService::class);
        $first=$service->start($user,$quiz); $second=$service->start($user,$quiz);
        self::assertSame($first->id,$second->id); self::assertSame(1,Attempt::count());
    }
    public function test_new_attempt_stores_ordered_option_arrays(): void
    {
        $attempt=$this->attempt(); $options=$attempt->questions()->firstOrFail()->options;
        self::assertTrue(array_is_list($options)); self::assertSame(['a','b','c','d'],array_column($options,'key'));
    }
    public function test_unassigned_learner_cannot_start_restricted_quiz(): void
    {
        $user=$this->person(); $quiz=$this->quiz(['access_mode'=>'assigned']);
        $this->actingAs($user)->postJson('/quizzes/'.$quiz->id.'/start')->assertUnprocessable();
        $quiz->users()->attach($user->id);
        $this->postJson('/quizzes/'.$quiz->id.'/start')->assertOk();
    }
    public function test_publishing_requires_enough_active_questions(): void
    {
        $quiz=$this->quiz(); $payload=$quiz->only(['category_id','title','question_count','duration_minutes','pass_percentage','max_attempts','shuffle_questions','shuffle_options','review_answers','access_mode','is_published']);
        $payload['question_count']=10;
        $this->actingAs($this->administrator())->postJson('/admin/quizzes',$payload)->assertUnprocessable()->assertJsonValidationErrors('question_count');
    }
    public function test_attempt_limits_are_enforced_after_submission(): void
    {
        $user=$this->person(); $quiz=$this->quiz(['max_attempts'=>1]); $a=$this->attempt($user,$quiz); app(QuizAttemptService::class)->finalize($a,true);
        $this->actingAs($user)->postJson('/quizzes/'.$quiz->id.'/start')->assertUnprocessable();
    }
    public function test_live_attempt_does_not_expose_answer_key_or_explanation(): void
    {
        $user=$this->person(); $attempt=$this->attempt($user);
        $r=$this->actingAs($user)->getJson(route('attempts.show',$attempt))->assertOk();
        self::assertStringNotContainsString('correct_option',$r->json('html')); self::assertStringNotContainsString('SERVER-ONLY-EXPLANATION',$r->json('html'));
    }
    public function test_learner_cannot_view_or_answer_someone_elses_attempt(): void
    {
        $a=$this->attempt(); $item=$a->questions()->firstOrFail();
        $this->actingAs($this->person())->getJson(route('attempts.show',$a))->assertForbidden();
        $this->patchJson(route('attempts.answer',[$a,'question'=>$item->id]),['selected_option'=>'a'])->assertForbidden();
    }
    public function test_question_id_must_belong_to_the_owned_attempt(): void
    {
        $user=$this->person(); $a=$this->attempt($user); $other=$this->attempt(); $foreign=$other->questions()->firstOrFail();
        $this->actingAs($user)->patchJson(route('attempts.answer',[$a,'question'=>$foreign->id]),['selected_option'=>'a'])->assertNotFound();
    }
    public function test_client_supplied_marks_and_correct_answers_are_ignored(): void
    {
        $user=$this->person(); $a=$this->attempt($user); $item=$a->questions()->firstOrFail();
        $this->actingAs($user)->patchJson(route('attempts.answer',[$a,'question'=>$item->id]),['selected_option'=>'b','correct_option'=>'b','percentage'=>100])->assertOk();
        $this->postJson(route('attempts.submit',$a),['percentage'=>100])->assertOk();
        self::assertSame('0.00',$a->fresh()->percentage); self::assertSame('a',$item->fresh()->correct_option);
    }
    public function test_question_edits_do_not_change_snapshot_scoring(): void
    {
        $a=$this->attempt(); $service=app(QuizAttemptService::class); $item=$a->questions()->firstOrFail();
        Question::whereKey($item->question_id)->update(['correct_option'=>'d','explanation'=>'Changed after start']);
        $service->answer($a,$item->id,'a'); $result=$service->finalize($a,true);
        self::assertSame(1,$result->correct_count); self::assertSame('a',$item->fresh()->correct_option); self::assertStringContainsString('SERVER-ONLY',$item->fresh()->explanation);
    }
    public function test_deadline_rejects_late_answers_and_grades_saved_answers(): void
    {
        $user=$this->person(); $a=$this->attempt($user); $items=$a->questions()->get(); $service=app(QuizAttemptService::class);
        $service->answer($a,$items[0]->id,'a'); $this->travelTo($a->expires_at->addSecond());
        $this->actingAs($user)->patchJson(route('attempts.answer',[$a,'question'=>$items[1]->id]),['selected_option'=>'a'])->assertOk();
        self::assertSame('expired',$a->fresh()->status); self::assertSame(1,$a->fresh()->correct_count); self::assertNull($items[1]->fresh()->selected_option);
    }
    public function test_submission_is_idempotent_and_final_answers_are_immutable(): void
    {
        $a=$this->attempt(); $service=app(QuizAttemptService::class); $item=$a->questions()->firstOrFail();
        $service->answer($a,$item->id,'a'); $first=$service->finalize($a,true); $submitted=$first->submitted_at->toIso8601String();
        $this->travel(10)->minutes(); $service->answer($a,$item->id,'b'); $again=$service->finalize($a,true);
        self::assertSame('a',$item->fresh()->selected_option); self::assertSame($submitted,$again->submitted_at->toIso8601String());
        self::assertSame(1,AuditLog::where('entity_type','Attempt')->where('entity_id',$a->id)->where('action','submitted')->count());
    }
    public function test_review_disabled_quiz_hides_explanations_after_submission(): void
    {
        $user=$this->person(); $a=$this->attempt($user,$this->quiz(['review_answers'=>false])); app(QuizAttemptService::class)->finalize($a,true);
        $r=$this->actingAs($user)->getJson(route('attempts.show',$a))->assertOk();
        self::assertStringNotContainsString('SERVER-ONLY-EXPLANATION',$r->json('html')); self::assertStringContainsString('Answer review is disabled',$r->json('html'));
    }
    public function test_csv_exports_neutralize_formula_prefixes(): void
    {
        $user=$this->person(['name'=>'=1+1']); $a=$this->attempt($user); app(QuizAttemptService::class)->finalize($a,true);
        $r=$this->actingAs($this->administrator())->get('/admin/reports/export')->assertOk();
        self::assertStringContainsString("'=1+1",$r->streamedContent());
    }
    public function test_default_and_demo_seeds_are_repeatable_without_overwriting_existing_theme_edits(): void
    {
        $theme=Theme::where('name','Ocean')->firstOrFail(); $theme->update(['accent'=>'#112233']);
        $this->seed(DemoContentSeeder::class); $this->seed(DemoContentSeeder::class);
        self::assertSame(6,Theme::count()); self::assertSame(5,Category::count()); self::assertSame(40,Question::count()); self::assertSame(5,Quiz::count()); self::assertSame('#112233',$theme->fresh()->accent);
    }
}
