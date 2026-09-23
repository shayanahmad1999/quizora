<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Domain\ScoreCalculator;
use PHPUnit\Framework\TestCase;
final class ScoreCalculatorTest extends TestCase
{
    public function test_unanswered_and_incorrect_choices_earn_zero(): void
    {
        $score=(new ScoreCalculator)->calculate([['selected'=>'a','correct'=>'a'],['selected'=>null,'correct'=>'b'],['selected'=>'c','correct'=>'d']],60);
        self::assertSame(1,$score['correct']); self::assertSame(3,$score['total']); self::assertSame(33.33,$score['percentage']); self::assertFalse($score['passed']);
    }
    public function test_passing_uses_exact_fraction_not_rounded_display(): void
    {
        $answers=[['selected'=>'a','correct'=>'a'],['selected'=>'b','correct'=>'b'],['selected'=>null,'correct'=>'c']];
        self::assertFalse((new ScoreCalculator)->calculate($answers,67)['passed']);
        self::assertTrue((new ScoreCalculator)->calculate($answers,66)['passed']);
    }
    public function test_perfect_score(): void
    {
        $s=(new ScoreCalculator)->calculate([['selected'=>'a','correct'=>'a']],100);
        self::assertTrue($s['passed']); self::assertSame(100.0,$s['percentage']);
    }
    public function test_empty_attempts_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class); (new ScoreCalculator)->calculate([],60);
    }
    public function test_invalid_pass_threshold_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class); (new ScoreCalculator)->calculate([['selected'=>'a','correct'=>'a']],101);
    }
}
