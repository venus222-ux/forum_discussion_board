<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiModerationService
{
    public function analyze(string $text): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/moderations', [
                'model' => 'omni-moderation-latest',
                'input' => $text,
            ]);

        $result = $response->json();
        $scores = $result['results'][0]['category_scores'] ?? [];

        // Use 'toxicity' category if available
        $toxicityScore = isset($scores['toxicity']) ? $scores['toxicity'] : 0.0;
        $toxicityLabel = $this->getToxicityLabel($toxicityScore);

        return [
            'toxicity_label' => $toxicityLabel,
            'toxicity_score' => $toxicityScore,
            'toxicity_reason' => 'Predicted via moderation API',
        ];
    }

    public function detectHateSpeech(string $text): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/moderations', [
                'model' => 'omni-moderation-latest',
                'input' => $text,
            ]);

        $result = $response->json();
        $scores = $result['results'][0]['category_scores'] ?? [];

        $hateScore = isset($scores['hate']) ? $scores['hate'] : 0.0;

        if ($hateScore >= 0.85) {
            $label = 'hate';
            $reason = 'Severe hate speech detected';
        } elseif ($hateScore >= 0.60) {
            $label = 'offensive';
            $reason = 'Potentially offensive content';
        } else {
            $label = 'safe';
            $reason = 'No hate speech detected';
        }

        return [
            'hate_label' => $label,
            'hate_score' => $hateScore,
            'hate_reason' => $reason,
        ];
    }

    private function getToxicityLabel(float $score): string
    {
        if ($score >= 0.60) return 'toxic';   // Lower threshold for testing
        return 'safe';
    }
}
