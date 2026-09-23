<?php
declare(strict_types=1);
// No Composer dependencies: these checks run against the actual pure scoring class.
require __DIR__.'/../app/Domain/ScoreCalculator.php';
use App\Domain\ScoreCalculator;
$scores=new ScoreCalculator; $count=0;
function check(bool $condition, string $label): void { global $count; if (!$condition) { fwrite(STDERR,"FAIL: {$label}\n"); exit(1); } $count++; echo "PASS: {$label}\n"; }
$answers=[['selected'=>'a','correct'=>'a'],['selected'=>null,'correct'=>'b'],['selected'=>'c','correct'=>'d']];
$s=$scores->calculate($answers,60);
check($s['correct']===1,'Correct selections counted');
check($s['total']===3,'All snapshot questions counted');
check($s['percentage']===33.33,'Percentage rounds to two decimals');
check(!$s['passed'],'Below-threshold attempt fails');
$two=[['selected'=>'a','correct'=>'a'],['selected'=>'b','correct'=>'b'],['selected'=>null,'correct'=>'c']];
check(!$scores->calculate($two,67)['passed'],'Unrounded fraction controls a 67 percent threshold');
check($scores->calculate($two,66)['passed'],'Unrounded fraction passes a 66 percent threshold');
check($scores->calculate([['selected'=>'a','correct'=>'a']],100)['passed'],'Perfect score passes a 100 percent threshold');
check($scores->calculate([['selected'=>null,'correct'=>'a']],60)['percentage']===0.0,'Unanswered attempt scores zero');
check($scores->calculate([['selected'=>'d','correct'=>'a']],60)['correct']===0,'Incorrect selection earns no marks');
check($scores->calculate([['selected'=>'a','correct'=>'a'],['selected'=>null,'correct'=>'b']],50)['passed'],'Exact pass boundary is inclusive');
foreach ([-1,101] as $threshold) { $thrown=false; try { $scores->calculate($answers,$threshold); } catch (InvalidArgumentException) { $thrown=true; } check($thrown,'Invalid threshold '.$threshold.' is rejected'); }
$thrown=false;try{$scores->calculate([],60);}catch(InvalidArgumentException){$thrown=true;}check($thrown,'Empty attempts are rejected');
$generator=(function(){yield ['selected'=>'a','correct'=>'a'];yield ['selected'=>null,'correct'=>'b'];})();
check($scores->calculate($generator,50)['total']===2,'Iterable/generator inputs are supported');
echo "{$count} domain checks passed.\n";
