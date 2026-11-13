<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A short "what should I teach next" briefing for an instructor.
 *
 * The briefing is built from the data with fixed rules, so it always works.
 * Optionally the AI can rewrite the same facts as a short paragraph. The AI
 * only gets counts and concept names, never student names.
 */
class ClassBriefingService
{
    public function __construct(
        private InsightsService $insights,
        private LearnerModelService $learner,
    ) {
    }

    /**
     * @return array{has_data:bool, headline:string, points:array<int,string>, attention:array<int,array<string,mixed>>, facts:array<string,mixed>}
     */
    public function briefing(User $instructor): array
    {
        $students = User::where('role', 'student')->where('instructor_id', $instructor->id)->orderBy('name')->get();

        // Which students are weak in which concept (learner model).
        $weakByConcept = [];
        $attention = [];
        foreach ($students as $s) {
            $weakest = null;
            foreach ($this->learner->masteryFor($s) as $m) {
                if ($m['status'] === 'weak') {
                    $weakByConcept[$m['concept']] = ($weakByConcept[$m['concept']] ?? 0) + 1;
                    if ($weakest === null || $m['mastery'] < $weakest['mastery']) {
                        $weakest = $m;
                    }
                }
            }
            if ($weakest !== null) {
                $attention[] = ['name' => $s->name, 'concept' => $weakest['concept'], 'percent' => $weakest['percent'], 'mastery' => $weakest['mastery']];
            }
        }
        arsort($weakByConcept);
        usort($attention, fn ($a, $b) => $a['mastery'] <=> $b['mastery']);

        $misconceptions = $this->insights->classMisconceptions($instructor, 3);
        $stuck = array_slice($this->insights->classStuck($instructor), 0, 2);

        $facts = [
            'studentCount' => $students->count(),
            'studentsWeakByConcept' => $weakByConcept,
            'topMisconceptions' => array_map(fn ($m) => ['label' => $m['label'], 'concept' => $m['concept'], 'count' => $m['count']], $misconceptions),
            'slowestPuzzles' => array_map(fn ($p) => [
                'concept' => $p['concept'], 'type' => $p['type'], 'avgSeconds' => $p['avg_time_seconds'], 'failRatePercent' => $p['fail_rate'],
            ], $stuck),
        ];

        $hasData = ! empty($weakByConcept) || ! empty($misconceptions) || ! empty($stuck);
        if (! $hasData) {
            return ['has_data' => false, 'headline' => 'Not enough data yet.', 'points' => [], 'attention' => [], 'facts' => $facts];
        }

        $points = [];
        if (! empty($weakByConcept)) {
            $concept = array_key_first($weakByConcept);
            $points[] = sprintf('%d of %d students are weak in %s. Make it the focus of your next lesson.', $weakByConcept[$concept], max(1, $students->count()), $concept);
        }
        if (! empty($misconceptions)) {
            $m = $misconceptions[0];
            $points[] = sprintf('Most common mistake: "%s" (%d times). %s', $m['label'], $m['count'], $m['advice']);
        }
        if (! empty($stuck)) {
            $p = $stuck[0];
            $points[] = sprintf('Slowest puzzle: %s (%s), about %dm %ds on average, %d%% fail rate.', $p['title'], $p['concept'], intdiv($p['avg_time_seconds'], 60), $p['avg_time_seconds'] % 60, $p['fail_rate']);
        }

        $headline = ! empty($weakByConcept)
            ? 'Focus next on '.array_key_first($weakByConcept).'.'
            : 'Your class is doing fine. Keep an eye on the slowest puzzle.';

        return ['has_data' => true, 'headline' => $headline, 'points' => $points, 'attention' => array_slice($attention, 0, 5), 'facts' => $facts];
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key')) && ! empty(config('services.anthropic.model'));
    }

    public function cachedNarrative(User $instructor): ?string
    {
        return Cache::get($this->cacheKey($instructor));
    }

    /** Ask the AI to write the briefing as a short paragraph. Null on any failure. */
    public function narrative(User $instructor): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $facts = $this->briefing($instructor)['facts'];

        try {
            $response = Http::timeout((int) config('services.anthropic.generate_timeout', 30))
                ->withHeaders(['x-api-key' => config('services.anthropic.key'), 'anthropic-version' => '2023-06-01'])
                ->acceptJson()
                ->post(config('services.anthropic.url'), [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => 400,
                    'system' => $this->systemPrompt(),
                    'messages' => [['role' => 'user', 'content' => json_encode($facts)]],
                ]);

            if (! $response->successful()) {
                Log::warning('AI class briefing failed', ['status' => $response->status()]);

                return null;
            }

            $text = $this->clean($response->json('content.0.text'));
        } catch (Throwable $e) {
            Log::warning('AI class briefing error: '.$e->getMessage());

            return null;
        }

        if ($text !== null) {
            Cache::put($this->cacheKey($instructor), $text, now()->addHours(6));
        }

        return $text;
    }

    /** Plain text only, a sensible length, no JSON. Public for tests. */
    public function clean(mixed $text): ?string
    {
        if (! is_string($text)) {
            return null;
        }
        $text = trim($text);
        if ($text === '' || str_starts_with($text, '{') || str_starts_with($text, '[') || mb_strlen($text) < 40 || mb_strlen($text) > 900) {
            return null;
        }

        return $text;
    }

    private function cacheKey(User $instructor): string
    {
        return 'class-briefing-narrative-'.$instructor->id;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You help a teacher of a children's software testing class decide what to teach next.
You receive counts already calculated from the class data (no names). Use ONLY those facts.
Never invent numbers or students. Write 2 to 4 short, plain sentences: what the class struggles with,
the most common mistake, and one concrete suggestion for the next lesson.
Reply with plain text only, no JSON, no lists, no headings.
PROMPT;
    }
}
