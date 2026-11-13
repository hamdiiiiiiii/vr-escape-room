<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\PuzzleResult;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Read-only learning insights derived from data that already exists
 * (game_sessions, puzzle_results, reports, rooms.config).
 *
 * Nothing here writes to the database, so it is safe to call from any page
 * and from the future AI feature ("which puzzles should this student
 * practise next?").
 */
class InsightsService
{
    /** A concept or puzzle averaging below this score counts as a weak point. */
    public const WEAK_BELOW = 70;

    /** XP needed for each rank, lowest first. */
    public const RANKS = [
        ['name' => 'Rookie',        'xp' => 0],
        ['name' => 'Bug Spotter',   'xp' => 100],
        ['name' => 'Tester',        'xp' => 300],
        ['name' => 'Bug Hunter',    'xp' => 600],
        ['name' => 'Chief Tester',  'xp' => 1000],
    ];

    // ---------------------------------------------------------------
    // Content pack (instructor page)
    // ---------------------------------------------------------------

    /**
     * Rooms with their puzzles, read from rooms.config.
     *
     * @return array<int, array<string, mixed>>
     */
    public function contentPack(): array
    {
        return Room::orderBy('id')->get()->map(function (Room $room) {
            $config = $room->config ?? [];
            $puzzles = collect($config['puzzles'] ?? [])->values()->map(
                fn (array $p, int $i) => $this->describePuzzle($p, $i + 1)
            )->all();

            return [
                'id' => (int) $room->id,
                'name' => $room->name,
                'description' => $config['description'] ?? '',
                'time_limit_minutes' => (int) ceil($room->time_limit_seconds / 60),
                'puzzle_count' => count($puzzles),
                'max_hints' => collect($config['puzzles'] ?? [])->max(fn ($p) => count($p['hints'] ?? [])) ?? 0,
                'puzzles' => $puzzles,
            ];
        })->all();
    }

    /** puzzle_id => ['title','type','concept','room'] for every puzzle in every room. */
    public function puzzleIndex(): array
    {
        $index = [];
        foreach (Room::orderBy('id')->get() as $room) {
            foreach (($room->config['puzzles'] ?? []) as $i => $p) {
                $d = $this->describePuzzle($p, $i + 1);
                $index[$p['id'] ?? ''] = $d + ['room' => $room->name];
            }
        }

        return $index;
    }

    // ---------------------------------------------------------------
    // Student profile: rank, XP, badges, weak points
    // ---------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function studentProfile(User $student): array
    {
        $sessions = GameSession::with(['report', 'puzzleResults', 'room'])
            ->where('user_id', $student->id)
            ->get();

        $finished = $sessions->where('status', 'finished')->filter(fn ($s) => $s->report !== null);
        $results = $finished->flatMap(fn ($s) => $s->puzzleResults);

        $xp = (int) $finished->sum(fn ($s) => (int) $s->report->session_score)
            + 25 * $results->filter(fn ($r) => $r->solved && (int) $r->hints_used === 0)->count();

        $rank = $this->rankFor($xp);

        return [
            'student_id' => (int) $student->id,
            'xp' => $xp,
            'rank' => $rank,
            'sessions_played' => $sessions->count(),
            'rooms_escaped' => $finished->pluck('room_id')->unique()->count(),
            'average_score' => $finished->count() ? (int) round($finished->avg(fn ($s) => $s->report->session_score)) : null,
            'best_score' => $finished->count() ? (int) $finished->max(fn ($s) => $s->report->session_score) : null,
            'best_time_seconds' => $finished->count() ? (int) $finished->min(fn ($s) => $s->report->total_time_seconds) : null,
            'total_hints' => (int) $results->sum('hints_used'),
            'badges' => $this->badges($finished, $results),
            'room_status' => $this->roomStatus($sessions),
            'concepts' => $this->conceptStats($results),
            'weak_points' => $this->weakPoints($results),
            'misconceptions' => app(MisconceptionService::class)->detect($results),
        ];
    }

    /**
     * JSON-friendly payload meant for the future AI personalisation feature.
     *
     * @return array<string, mixed>
     */
    public function weakPointsPayload(User $student): array
    {
        $p = $this->studentProfile($student);

        return [
            'student_id' => $p['student_id'],
            'student_name' => $student->name,
            'rank' => $p['rank']['name'],
            'xp' => $p['xp'],
            'weak_threshold' => self::WEAK_BELOW,
            'concepts' => $p['concepts'],
            'weak_points' => $p['weak_points'],
            // Named mistakes found from the wrong answers (fixed rules, no AI needed).
            'misconceptions' => $p['misconceptions'],
            // Learner model (Bayesian Knowledge Tracing) and recommender.
            'mastery' => app(LearnerModelService::class)->masteryFor($student),
            'recommendations' => app(LearnerModelService::class)->recommend($student),
        ];
    }

    /** @return array{name:string,level:int,xp:int,next_name:?string,next_xp:?int,progress:int} */
    public function rankFor(int $xp): array
    {
        $level = 0;
        foreach (self::RANKS as $i => $r) {
            if ($xp >= $r['xp']) {
                $level = $i;
            }
        }
        $next = self::RANKS[$level + 1] ?? null;
        $cur = self::RANKS[$level];
        $progress = $next ? (int) round(($xp - $cur['xp']) / ($next['xp'] - $cur['xp']) * 100) : 100;

        return [
            'name' => $cur['name'],
            'level' => $level + 1,
            'xp' => $xp,
            'next_name' => $next['name'] ?? null,
            'next_xp' => $next['xp'] ?? null,
            'progress' => max(0, min(100, $progress)),
        ];
    }

    /**
     * @return array<int, array{key:string,name:string,description:string,earned:bool}>
     */
    private function badges(Collection $finished, Collection $results): array
    {
        $solvedClean = $results->filter(fn ($r) => $r->solved && (int) $r->hints_used === 0)->count();
        $flawless = $finished->contains(function ($s) {
            $r = $s->puzzleResults;

            return $r->count() > 0
                && $r->every(fn ($x) => $x->solved && (int) $x->hints_used === 0 && (int) $x->attempts <= 1);
        });
        $fast = $finished->contains(function ($s) {
            $limit = (int) ($s->room?->time_limit_seconds ?? 0);

            return $limit > 0 && (int) $s->report->total_time_seconds <= $limit / 2;
        });

        $defs = [
            ['first_escape', 'First Escape', 'Finish your first room.', $finished->count() >= 1],
            ['no_hints', 'Clean Solve', 'Solve a puzzle without any hint.', $solvedClean >= 1],
            ['flawless', 'Flawless Run', 'Finish a room with no hints and no wrong tries.', $flawless],
            ['speed', 'Speed Runner', 'Escape in half the time limit or less.', $fast],
            ['sharp_eye', 'Sharp Eye', 'Reach an average score of 80 or more (2+ sessions).',
                $finished->count() >= 2 && $finished->avg(fn ($s) => $s->report->session_score) >= 80],
            ['persistent', 'Persistent', 'Finish 3 sessions.', $finished->count() >= 3],
        ];

        return array_map(fn ($d) => [
            'key' => $d[0], 'name' => $d[1], 'description' => $d[2], 'earned' => (bool) $d[3],
        ], $defs);
    }

    /**
     * Per room: escaped / in_progress / not_played.
     *
     * @return array<int, string>
     */
    private function roomStatus(Collection $sessions): array
    {
        $status = [];
        foreach (Room::orderBy('id')->pluck('id') as $roomId) {
            $mine = $sessions->where('room_id', $roomId);
            $status[(int) $roomId] = match (true) {
                $mine->contains('status', 'finished') => 'escaped',
                $mine->contains('status', 'in_progress') => 'in_progress',
                default => 'not_played',
            };
        }

        return $status;
    }

    /**
     * Average performance per concept.
     *
     * @return array<int, array<string, mixed>>
     */
    private function conceptStats(Collection $results): array
    {
        return $results->groupBy('concept_tag')->map(function (Collection $rows, string $tag) {
            $avg = (int) round($rows->avg('score'));

            return [
                'concept' => $tag,
                'avg_score' => $avg,
                'puzzles_played' => $rows->count(),
                'solved_rate' => (int) round($rows->where('solved', true)->count() / max(1, $rows->count()) * 100),
                'avg_attempts' => round($rows->avg('attempts'), 1),
                'avg_hints' => round($rows->avg('hints_used'), 1),
                'avg_time_seconds' => (int) round($rows->avg('time_seconds')),
                'is_weak' => $avg < self::WEAK_BELOW,
            ];
        })->sortBy('avg_score')->values()->all();
    }

    /**
     * Puzzles the student struggled with, weakest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function weakPoints(Collection $results): array
    {
        $index = $this->puzzleIndex();

        return $results->groupBy('puzzle_id')->map(function (Collection $rows, string $pid) use ($index) {
            $meta = $index[$pid] ?? ['title' => $pid, 'type' => null, 'concept' => $rows->first()->concept_tag];

            return [
                'puzzle_id' => $pid,
                'title' => $meta['title'],
                'type' => $meta['type'],
                'concept' => $rows->first()->concept_tag,
                'avg_score' => (int) round($rows->avg('score')),
                'times_played' => $rows->count(),
                'times_failed' => $rows->where('solved', false)->count(),
                'avg_attempts' => round($rows->avg('attempts'), 1),
                'avg_hints' => round($rows->avg('hints_used'), 1),
                'avg_time_seconds' => (int) round($rows->avg('time_seconds')),
                'wrong_answers' => $this->commonWrongAnswers($rows, 3),
            ];
        })->filter(fn ($p) => $p['avg_score'] < self::WEAK_BELOW)
            ->sortBy('avg_score')->values()->all();
    }

    // ---------------------------------------------------------------
    // Instructor: where the class gets stuck
    // ---------------------------------------------------------------

    /**
     * Per-puzzle difficulty for the instructor's assigned students,
     * hardest (slowest) first.
     *
     * @return array<int, array<string, mixed>>
     */
    /** The most common misconceptions across the instructor's students. */
    public function classMisconceptions(User $instructor, int $limit = 5): array
    {
        $ids = User::where('instructor_id', $instructor->id)->pluck('id');
        if ($ids->isEmpty()) {
            return [];
        }

        $results = PuzzleResult::whereNotNull('wrong_answers')
            ->whereHas('gameSession', fn ($q) => $q->whereIn('user_id', $ids))->get();

        return array_slice(app(MisconceptionService::class)->detect($results), 0, $limit);
    }

    public function classStuck(User $instructor): array
    {
        $ids = User::where('instructor_id', $instructor->id)->pluck('id');
        if ($ids->isEmpty()) {
            return [];
        }

        $results = PuzzleResult::whereHas('gameSession', fn ($q) => $q->whereIn('user_id', $ids))->get();
        $index = $this->puzzleIndex();

        return $results->groupBy('puzzle_id')->map(function (Collection $rows, string $pid) use ($index) {
            $meta = $index[$pid] ?? ['title' => $pid, 'type' => null];

            return [
                'puzzle_id' => $pid,
                'title' => $meta['title'],
                'type' => $meta['type'],
                'concept' => $rows->first()->concept_tag,
                'plays' => $rows->count(),
                'avg_time_seconds' => (int) round($rows->avg('time_seconds')),
                'avg_hints' => round($rows->avg('hints_used'), 1),
                'avg_attempts' => round($rows->avg('attempts'), 1),
                'fail_rate' => (int) round($rows->where('solved', false)->count() / max(1, $rows->count()) * 100),
                'common_wrong' => $this->commonWrongAnswers($rows),
            ];
        })->sortByDesc('avg_time_seconds')->values()->all();
    }

    /**
     * Rank and weakest concept for each assigned student.
     *
     * @return array<int, array{rank:string,weakest:?string}>  keyed by student id
     */
    public function classStudentBadges(User $instructor): array
    {
        $out = [];
        foreach (User::where('instructor_id', $instructor->id)->get() as $s) {
            $p = $this->studentProfile($s);
            $weak = collect($p['concepts'])->firstWhere('is_weak', true);
            $out[(int) $s->id] = [
                'rank' => $p['rank']['name'],
                'xp' => $p['xp'],
                'weakest' => $weak['concept'] ?? null,
            ];
        }

        return $out;
    }

    // ---------------------------------------------------------------
    // Report page: per-puzzle log versus the class
    // ---------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    public function puzzleLog(int $sessionId): array
    {
        $session = GameSession::with('puzzleResults')->find($sessionId);
        if (! $session) {
            return [];
        }
        $index = $this->puzzleIndex();

        return $session->puzzleResults->values()->map(function (PuzzleResult $r, int $i) use ($index) {
            $classAvg = PuzzleResult::where('puzzle_id', $r->puzzle_id)->avg('time_seconds');
            $ratio = $classAvg > 0 ? $r->time_seconds / $classAvg : 1;
            $meta = $index[$r->puzzle_id] ?? ['title' => $r->puzzle_id, 'type' => null];

            return [
                'stage' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'title' => $meta['title'],
                'type' => $meta['type'],
                'time' => sprintf('%02d:%02d', intdiv($r->time_seconds, 60), $r->time_seconds % 60),
                'vs_class' => $ratio < 0.85 ? 'faster' : ($ratio > 1.15 ? 'slower' : 'on par'),
                'bar' => (int) max(8, min(100, round($ratio * 70))),
                'hints' => (int) $r->hints_used,
                'attempts' => (int) $r->attempts,
                'solved' => (bool) $r->solved,
                'wrong' => collect($r->wrong_answers ?? [])->map(fn ($a) => $this->formatAnswer($a))->all(),
                'misconceptions' => app(MisconceptionService::class)->labelsFor($r),
            ];
        })->all();
    }

    /**
     * The wrong answers players gave most often.
     *
     * @return array<int, array{answer:string,count:int}>
     */
    public function commonWrongAnswers(Collection $results, int $limit = 2): array
    {
        return $results
            ->flatMap(fn ($r) => $r->wrong_answers ?? [])
            ->map(fn ($a) => $this->formatAnswer($a))
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->map(fn ($count, $answer) => ['answer' => (string) $answer, 'count' => $count])
            ->values()
            ->all();
    }

    private function formatAnswer(mixed $answer): string
    {
        return is_scalar($answer) ? (string) $answer : (string) json_encode($answer);
    }

    // ---------------------------------------------------------------

    private function describePuzzle(array $p, int $n): array
    {
        $prompt = (string) ($p['prompt'] ?? '');
        $type = (string) ($p['type'] ?? '');
        $title = match ($type) {
            'observation' => 'Spot the bug',
            'assembly' => 'Build the test',
            default => 'Puzzle',
        } . ' ' . $n;

        return [
            'n' => str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'id' => $p['id'] ?? '',
            'title' => $title,
            'prompt' => Str::limit($prompt, 140),
            'type' => $type,
            'concept' => $p['conceptTag'] ?? '',
            'hints' => count($p['hints'] ?? []),
        ];
    }
}
