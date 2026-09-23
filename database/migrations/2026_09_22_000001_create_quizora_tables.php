<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $t) {
            $t->id(); $t->string('name', 80)->unique(); $t->string('layout', 20)->default('sidebar');
            $t->string('accent', 7); $t->string('background', 7); $t->string('surface', 7);
            $t->string('text', 7); $t->boolean('is_dark')->default(false); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name', 100); $t->string('email', 190)->unique(); $t->string('password');
            $t->string('role', 20)->default('learner'); $t->boolean('is_active')->default(true);
            $t->boolean('must_change_password')->default(true); $t->unsignedInteger('auth_version')->default(1);
            $t->string('theme_mode', 20)->default('random_login');
            $t->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('last_theme_id')->nullable()->constrained('themes')->nullOnDelete();
            $t->timestamp('last_login_at')->nullable(); $t->rememberToken(); $t->timestamps(); $t->softDeletes();
            $t->index(['role', 'is_active']); $t->index('theme_id');
        });
        Schema::create('theme_user', function (Blueprint $t) {
            $t->foreignId('theme_id')->constrained()->cascadeOnDelete(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['theme_id','user_id']); $t->index('user_id');
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('name', 100)->unique(); $t->text('description')->nullable();
            $t->string('color', 7)->default('#167D8D'); $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('category_id')->constrained()->restrictOnDelete(); $t->text('prompt');
            $t->json('options'); $t->string('correct_option', 1); $t->text('explanation')->nullable();
            $t->string('difficulty', 10)->default('medium'); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->softDeletes(); $t->index(['category_id','is_active']);
        });
        Schema::create('quizzes', function (Blueprint $t) {
            $t->id(); $t->foreignId('category_id')->constrained()->restrictOnDelete(); $t->string('title', 150);
            $t->text('description')->nullable(); $t->unsignedSmallInteger('question_count')->default(10);
            $t->unsignedSmallInteger('duration_minutes')->default(15); $t->unsignedTinyInteger('pass_percentage')->default(60);
            $t->unsignedSmallInteger('max_attempts')->default(3); $t->boolean('shuffle_questions')->default(true);
            $t->boolean('shuffle_options')->default(true); $t->boolean('review_answers')->default(true);
            $t->string('access_mode', 12)->default('all'); $t->boolean('is_published')->default(false);
            $t->timestamps(); $t->softDeletes(); $t->index(['category_id','is_published']);
        });
        Schema::create('quiz_user', function (Blueprint $t) {
            $t->foreignId('quiz_id')->constrained()->cascadeOnDelete(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['quiz_id','user_id']); $t->index('user_id');
        });
        Schema::create('attempts', function (Blueprint $t) {
            $t->id(); $t->uuid('uuid')->unique(); $t->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete(); $t->string('quiz_title', 150); $t->string('category_name', 100);
            $t->string('status', 16)->default('in_progress'); $t->unsignedSmallInteger('total_questions');
            $t->unsignedTinyInteger('pass_percentage'); $t->boolean('review_answers');
            $t->unsignedSmallInteger('correct_count')->nullable(); $t->decimal('percentage', 5, 2)->nullable(); $t->boolean('passed')->nullable();
            $t->timestamp('started_at'); $t->timestamp('expires_at'); $t->timestamp('submitted_at')->nullable(); $t->timestamps();
            $t->index(['user_id','quiz_id','status']); $t->index(['status','expires_at']); $t->index('quiz_id');
        });
        Schema::create('attempt_questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->nullable()->constrained()->nullOnDelete(); $t->unsignedSmallInteger('sequence');
            $t->text('prompt'); $t->json('options'); $t->string('correct_option', 1); $t->text('explanation')->nullable();
            $t->string('selected_option', 1)->nullable(); $t->timestamp('answered_at')->nullable(); $t->timestamps();
            $t->unique(['attempt_id','sequence']);
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('site_name', 80)->default('Quizora'); $t->string('tagline', 180)->default('A little practice. A lot of possibility.');
            $t->string('support_email', 190)->nullable(); $t->foreignId('default_theme_id')->nullable()->constrained('themes')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 60); $t->string('entity_type', 80); $t->unsignedBigInteger('entity_id')->nullable();
            $t->string('description', 255); $t->timestamp('created_at'); $t->index(['entity_type','entity_id']);
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary(); $t->foreignId('user_id')->nullable()->index(); $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable(); $t->longText('payload'); $t->integer('last_activity')->index();
        });
        Schema::create('cache', function (Blueprint $t) { $t->string('key')->primary(); $t->mediumText('value'); $t->integer('expiration'); });
        Schema::create('cache_locks', function (Blueprint $t) { $t->string('key')->primary(); $t->string('owner'); $t->integer('expiration'); });
    }
    public function down(): void
    {
        foreach (['cache_locks','cache','sessions','audit_logs','settings','attempt_questions','attempts','quiz_user','quizzes','questions','categories','theme_user','users','themes'] as $table) { Schema::dropIfExists($table); }
    }
};
