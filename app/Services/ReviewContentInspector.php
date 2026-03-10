<?php

namespace App\Services;

class ReviewContentInspector
{
    /**
     * @return array{spam_score:int,has_offensive_language:bool,is_spam:bool}
     */
    public function inspect(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $score = 0;

        if ($normalized === '') {
            return [
                'spam_score' => 0,
                'has_offensive_language' => false,
                'is_spam' => false,
            ];
        }

        $offensiveTerms = (array) config('reviews.offensive_terms', []);
        $hasOffensiveLanguage = false;

        foreach ($offensiveTerms as $term) {
            $needle = mb_strtolower(trim((string) $term));
            if ($needle === '') {
                continue;
            }

            if (mb_strpos($normalized, $needle) !== false) {
                $hasOffensiveLanguage = true;
                $score += 2;
            }
        }

        if (preg_match('/https?:\/\//i', $normalized) === 1) {
            $score += 2;
        }

        if (preg_match('/(.)\1{7,}/u', $normalized) === 1) {
            $score += 1;
        }

        if (mb_strlen($normalized) < 12) {
            $score += 1;
        }

        $isSpam = $score >= (int) config('reviews.spam_threshold', 4);

        return [
            'spam_score' => $score,
            'has_offensive_language' => $hasOffensiveLanguage,
            'is_spam' => $isSpam,
        ];
    }
}
