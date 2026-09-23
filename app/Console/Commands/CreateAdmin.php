<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
final class CreateAdmin extends Command
{
    protected $signature = 'quizora:admin {--name=} {--email=}';
    protected $description = 'Create a super administrator; passwords are requested securely, never accepted as CLI arguments.';
    public function handle(): int
    {
        if (!$this->input->isInteractive()) { $this->error('Run this command interactively to enter a password securely.'); return self::FAILURE; }
        $name=$this->option('name') ?: $this->ask('Administrator name');
        $email=mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Administrator email'))));
        $password=$this->secret('Password (12+ characters, upper/lowercase and a number)');
        $confirmation=$this->secret('Confirm password');
        $v=Validator::make(['name'=>$name,'email'=>$email,'password'=>$password,'password_confirmation'=>$confirmation],[
            'name'=>'required|string|max:100','email'=>'required|email|max:190|unique:users,email','password'=>['required','confirmed','max:128',Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($v->fails()) { foreach ($v->errors()->all() as $message) { $this->error($message); } return self::FAILURE; }
        User::create(['name'=>$name,'email'=>$email,'password'=>$password,'role'=>'super_admin','is_active'=>true,'must_change_password'=>false,'theme_mode'=>'fixed','theme_id'=>\App\Models\Theme::where('is_active',true)->value('id')]);
        $this->info('Administrator created. Sign in with the email and password you entered.'); return self::SUCCESS;
    }
}
