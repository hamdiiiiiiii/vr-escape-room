<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiAnalysisService
{
    /**
     * @param array{sessionScore:int, level:string, endReason:string, concepts:array<int,array{conceptTag:string,score:int}>, puzzles:array} $summary
     * @return array{overallLevel:string, weakConcepts:array<int,string>, studentRecommendation:string, instructorNote:string}|null
     */
    public function analyze(array $summary): ?array
    {
        $key = config('services.anthropic.key');
        $model = config('services.anthropic.model');

        // No key or model: skip silently, the report still shows the scores.
        if (empty($key) || empty($model)) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('services.anthropic.timeout', 10))
                ->withHeaders([
                    'x-api-key' => $key,
                    'anthropic-version' => '2023-06-01',
                ])
                ->acceptJson()
                ->post(config('services.anthropic.url'), [
                    'model' => $model,
                    'max_tokens' => 600,
                    'system' => $this->systemPrompt(),
                    'messages' => [
                        ['role' => 'user', 'content' => json_encode($summary)],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('AI analysis failed', ['status' => $response->status()]);

                return null;
            }

            $text = $response->json('content.0.text');

            return $this->validate($text, $summary);
        } catch (Throwable $e) {
            Log::warning('AI analysis error: '.$e->getMessage());

            return null;
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You help teachers understand how a child did in a software testing escape room.
You receive scores that were already calculated. Never calculate or change scores.
Reply with ONLY a JSON object, no other text, with exactly these keys:
- "overallLevel": one of "Needs support", "Proficient", "Advanced" (use the level you were given)
- "weakConcepts": array of conceptTag strings taken only from the input (empty if none are weak)
- "studentRecommendation": 1-2 friendly, encouraging sentences for a child
- "instructorNote": 1-2 factual sentences for the teacher
PROMPT;
    }

    private function validate(?string $text, array $summary): ?array
    {
        if (! is_string($text)) {
            return null;
        }

        // Tolerate a ```json fence around the object
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
        $data = json_decode($text, true);

        if (! is_array($data)) {
            return null;
        }

        foreach (['overallLevel', 'weakConcepts', 'studentRecommendation', 'instructorNote'] as $field) {
            if (! array_key_exists($field, $data)) {
                return null;
            }
        }

        if (! in_array($data['overallLevel'], ['Needs support', 'Proficient', 'Advanced'], true)) {
            return null;
        }

        if (! is_array($data['weakConcepts'])) {
            return null;
        }

        $known = array_column($summary['concepts'] ?? [], 'conceptTag');
        foreach ($data['weakConcepts'] as $tag) {
            if (! is_string($tag) || ! in_array($tag, $known, true)) {
                return null;
            }
        }

        foreach (['studentRecommendation', 'instructorNote'] as $field) {
            if (! is_string($data[$field]) || $data[$field] === '' || mb_strlen($data[$field]) > 500) {
                return null;
            }
        }

        return [
            'overallLevel' => $data['overallLevel'],
            'weakConcepts' => array_values($data['weakConcepts']),
            'studentRecommendation' => $data['studentRecommendation'],
            'instructorNote' => $data['instructorNote'],
        ];
    }
}