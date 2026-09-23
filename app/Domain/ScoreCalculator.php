<?php
declare(strict_types=1);
namespace App\Domain;
/** Pure domain logic: marks never come from the browser. */
final class ScoreCalculator
{
    /** @param iterable<array{selected: ?string, correct: string}> $answers
     *  @return array{correct: int, total: int, percentage: float, passed: bool} */
    public function calculate(iterable $answers, int $passPercentage): array
    {
        if ($passPercentage < 0 || $passPercentage > 100) { throw new \InvalidArgumentException('Pass percentage must be 0 to 100.'); }
        $correct = 0; $total = 0;
        foreach ($answers as $answer) {
            $total++;
            if ($answer['selected'] !== null && $answer['selected'] === $answer['correct']) { $correct++; }
        }
        if ($total === 0) { throw new \InvalidArgumentException('An attempt must contain questions.'); }
        return ['correct'=>$correct, 'total'=>$total, 'percentage'=>round($correct * 100 / $total, 2), 'passed'=>$correct * 100 >= $passPercentage * $total];
    }
}
