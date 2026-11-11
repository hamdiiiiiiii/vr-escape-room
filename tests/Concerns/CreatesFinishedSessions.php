<?php
 
namespace Tests\Concerns;
 
use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Report;
use App\Models\Room;
use App\Models\User;
 
trait CreatesFinishedSessions
{
    protected function makeInstructor(): User
    {
        return User::factory()->create(['role' => 'instructor']);
    }
 
    protected function makeStudent(?User $instructor = null, ?string $name = null): User
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => $name ?? fake()->name(),
        ]);
 
        if ($instructor) {
            $student->forceFill(['instructor_id' => $instructor->id])->save();
        }
 
        return $student;
    }
 
    protected function makeRoom(): Room
    {
        return Room::firstOrCreate(
            ['name' => 'The Boundary Maze'],
            [
                'time_limit_seconds' => 900,
                'config' => [
                    'conceptTag' => 'Boundary Testing',
                    'puzzles' => [
                        ['id' => 'p1', 'conceptTag' => 'Boundary Testing'],
                        ['id' => 'p2', 'conceptTag' => 'Boundary Testing'],
                        ['id' => 'p3', 'conceptTag' => 'Boundary Testing'],
                    ],
                ],
            ]
        );
    }
 
    protected function makeSession(User $student, string $status = 'waiting'): GameSession
    {
        return GameSession::create([
            'user_id' => $student->id,
            'room_id' => $this->makeRoom()->id,
            'status' => $status,
            'end_reason' => $status === 'finished' ? 'escaped' : null,
            'expires_at' => now()->addMinutes(10),
            'started_at' => $status === 'waiting' ? null : now()->subMinutes(12),
            'ended_at' => $status === 'finished' ? now() : null,
        ]);
    }
 
    /**
     * A finished session with 3 solved puzzles:
     * 4 attempts in total, 1 hint used, 2 puzzles solved without help,
     * total time 705 seconds (11m 45s), room limit 900 seconds (15 minutes).
     */
    protected function makeFinishedSession(User $student, int $score = 92, ?array $concepts = null): GameSession
    {
        $session = $this->makeSession($student, 'finished');
 
        foreach ([['p1', 1, 0], ['p2', 2, 0], ['p3', 1, 1]] as [$puzzleId, $attempts, $hints]) {
            PuzzleResult::create([
                'game_session_id' => $session->id,
                'puzzle_id' => $puzzleId,
                'concept_tag' => 'Boundary Testing',
                'solved' => true,
                'attempts' => $attempts,
                'time_seconds' => 60,
                'hints_used' => $hints,
                'score' => $score,
            ]);
        }
 
        Report::create([
            'game_session_id' => $session->id,
            'total_time_seconds' => 705,
            'session_score' => $score,
            'level' => $score >= 80 ? 'Advanced' : ($score >= 50 ? 'Proficient' : 'Needs support'),
            'concept_scores' => $concepts ?? [
                'Boundary Testing' => 94,
                'Off-by-One Trap Detection' => 90,
            ],
            'ai_analysis' => null,
        ]);
 
        return $session;
    }
}