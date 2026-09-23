<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\{Setting,Theme};
use Illuminate\Database\Seeder;
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $presets=[
            ['Ocean','sidebar','#087F8C','#F3F6F8','#FFFFFF','#192D3D',false],
            ['Meadow','topbar','#326B4D','#F4F7EF','#FFFFFF','#273B2B',false],
            ['Amethyst','sidebar','#7455AB','#F5F2F9','#FFFFFF','#302746',false],
            ['Sunset','focus','#B65B3D','#FCF5EE','#FFFFFF','#44302A',false],
            ['Midnight','topbar','#466AB2','#111827','#1C273A','#E7EDF8',true],
            ['Paper','focus','#525B56','#F5F4EF','#FCFCF8','#303A34',false],
        ];
        foreach ($presets as [$name,$layout,$accent,$background,$surface,$text,$isDark]) {
            Theme::firstOrCreate(['name'=>$name],['layout'=>$layout,'accent'=>$accent,'background'=>$background,'surface'=>$surface,'text'=>$text,'is_dark'=>$isDark,'is_active'=>true]);
        }
        Setting::firstOrCreate(['id'=>1],['site_name'=>'Quizora','tagline'=>'A little practice. A lot of possibility.','default_theme_id'=>Theme::where('name','Ocean')->value('id')]);
    }
}
