<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\{Category,Question,Quiz};
use Illuminate\Database\Seeder;
final class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) { throw new \LogicException('Demo content is disabled in production.'); }
        $this->call(DatabaseSeeder::class);
        $subjects=json_decode(file_get_contents(__DIR__.'/data/demo-questions.json'),true,512,JSON_THROW_ON_ERROR);
        foreach ($subjects as $subject) {
            $category=Category::firstOrCreate(['name'=>$subject['name']],['description'=>$subject['description'],'color'=>$subject['color'],'is_active'=>true]);
            foreach ($subject['questions'] as $q) {
                Question::firstOrCreate(['category_id'=>$category->id,'prompt'=>$q['prompt']],['options'=>$q['options'],'correct_option'=>$q['correct_option'],'explanation'=>$q['explanation'],'difficulty'=>$q['difficulty'],'is_active'=>true]);
            }
            Quiz::firstOrCreate(['category_id'=>$category->id,'title'=>$subject['name'].' - First steps'],['description'=>$subject['description'],'question_count'=>5,'duration_minutes'=>10,'pass_percentage'=>60,'max_attempts'=>3,'shuffle_questions'=>true,'shuffle_options'=>true,'review_answers'=>true,'access_mode'=>'all','is_published'=>true]);
        }
    }
}
