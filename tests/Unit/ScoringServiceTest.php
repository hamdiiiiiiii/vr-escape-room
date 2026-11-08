<?php

namespace Tests\Unit;

use App\Services\ScoringService;
use PHPUnit\Framework\TestCase;

class ScoringServiceTest extends TestCase
{
    private ScoringService $scoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoring = new ScoringService();
    }

    public function test_attempt_scores(): void
    {
        $this->assertSame(100, $this->scoring->puzzleScore(true, 1, 0));
        $this->assertSame(70, $this->scoring->puzzleScore(true, 2, 0));
        $this->assertSame(40, $this->scoring->puzzleScore(true, 3, 0));
    }

    public function test_unsolved_scores_zero_even_with_attempts(): void
    {
        $this->assertSame(0, $this->scoring->puzzleScore(false, 3, 0));
        $this->assertSame(0, $this->scoring->puzzleScore(false, 3, 2));
    }

    public function test_any_hint_halves_the_score(): void
    {
        $this->assertSame(50, $this->scoring->puzzleScore(true, 1, 1));
        $this->assertSame(50, $this->scoring->puzzleScore(true, 1, 2));
        $this->assertSame(35, $this->scoring->puzzleScore(true, 2, 1));
        $this->assertSame(20, $this->scoring->puzzleScore(true, 3, 1));
    }

    public function test_level_boundaries(): void
    {
        $this->assertSame('Needs support', $this->scoring->level(0));
        $this->assertSame('Needs support', $this->scoring->level(49));
        $this->assertSame('Proficient', $this->scoring->level(50));
        $this->assertSame('Proficient', $this->scoring->level(79));
        $this->assertSame('Advanced', $this->scoring->level(80));
        $this->assertSame('Advanced', $this->scoring->level(100));
    }

    /** Sample sessions 1-3: whole-session summaries */
    public function test_six_sample_sessions(): void
    {
        // 1. perfect run
        $s1 = $this->scoring->summarize([
            ['concept_tag' => 'a', 'score' => 100],
            ['concept_tag' => 'b', 'score' => 100],
        ]);
        $this->assertSame(100, $s1['session_score']);
        $this->assertSame('Advanced', $s1['level']);

        // 2. good but one retry
        $s2 = $this->scoring->summarize([
            ['concept_tag' => 'a', 'score' => 100],
            ['concept_tag' => 'b', 'score' => 70],
        ]);
        $this->assertSame(85, $s2['session_score']);
        $this->assertSame('Advanced', $s2['level']);

        // 3. one solved with hint, one failed
        $s3 = $this->scoring->summarize([
            ['concept_tag' => 'a', 'score' => 50],
            ['concept_tag' => 'b', 'score' => 0],
        ]);
        $this->assertSame(25, $s3['session_score']);
        $this->assertSame('Needs support', $s3['level']);

        // 4. exactly on the Proficient boundary
        $s4 = $this->scoring->summarize([
            ['concept_tag' => 'a', 'score' => 100],
            ['concept_tag' => 'b', 'score' => 0],
        ]);
        $this->assertSame(50, $s4['session_score']);
        $this->assertSame('Proficient', $s4['level']);

        // 5. two puzzles share a concept: concept score is their average
        $s5 = $this->scoring->summarize([
            ['concept_tag' => 'a', 'score' => 100],
            ['concept_tag' => 'a', 'score' => 40],
            ['concept_tag' => 'b', 'score' => 70],
        ]);
        $this->assertSame(70, $s5['session_score']);
        $this->assertSame(['a' => 70, 'b' => 70], $s5['concept_scores']);
        $this->assertSame('Proficient', $s5['level']);

        // 6. no puzzles at all
        $s6 = $this->scoring->summarize([]);
        $this->assertSame(0, $s6['session_score']);
        $this->assertSame([], $s6['concept_scores']);
        $this->assertSame('Needs support', $s6['level']);
    }
}