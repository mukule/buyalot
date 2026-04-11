<?php

namespace App\Services;

class ProfanityFilterService
{
    protected static array $abusiveWords = [
        // English (common ones)
        'fuck', 'shit', 'asshole', 'bitch', 'bastard', 'cunt', 'dick', 'pussy', 'faggot', 'nigger', 'wanker', 'motherfucker',

        // Swahili (requested)
        'mbwa', 'matako', 'gasia', 'mjinga', 'mshenzi', 'mpumbavu', 'kinyesi', 'mavi', 'kuma', 'mboro', 'shoga', 'malaya'
    ];

    /**
     * Check if a string contains abusive words.
     *
     * @param string $text
     * @return bool
     */
    public function isAbusive(string $text): bool
    {
        $text = strtolower($text);

        foreach (self::$abusiveWords as $word) {
            // Use regex to match whole words to avoid false positives (e.g., "assessment")
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter abusive words from a string.
     *
     * @param string $text
     * @param string $replacement
     * @return string
     */
    public function filter(string $text, string $replacement = '***'): string
    {
        foreach (self::$abusiveWords as $word) {
            $text = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', $replacement, $text);
        }

        return $text;
    }
}
