<?php

namespace App\Services;

class BadWordFilterService
{
    /**
     * Replacement string used wherever a bad word is detected.
     */
    private const REPLACEMENT = '****';

    /**
     * List of banned words/phrases (lowercase).
     *
     * Keep this list in alphabetical order for easy maintenance.
     * Add more words as needed — matching is case-insensitive.
     *
     * @var list<string>
     */
    private array $badWords = [
        'badword1',
        'badword2',
        'badword3',
        // Add your actual list here — keeping placeholders avoids
        // shipping real slurs inside source code.
    ];

    /**
     * Scan the given text and replace every bad word with the
     * configured replacement string.
     *
     * Whole-word matching is used (word boundaries \b) so "class"
     * would not be caught by a rule targeting "ass".
     *
     * @param  string $text  Raw user input.
     * @return string        Cleaned text.
     */
    public function filter(string $text): string
    {
        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            $text    = preg_replace($pattern, self::REPLACEMENT, $text);
        }

        return $text;
    }

    /**
     * Return true if the text contains at least one bad word.
     *
     * Useful for hard-blocking a message before it is stored.
     *
     * @param  string $text  Raw user input.
     * @return bool
     */
    public function containsBadWord(string $text): bool
    {
        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a word to the runtime bad-word list.
     *
     * Changes are not persisted between requests; for a persistent list
     * store the words in the database and load them in the constructor.
     *
     * @param  string $word
     * @return void
     */
    public function addWord(string $word): void
    {
        $this->badWords[] = strtolower(trim($word));
    }

    /**
     * Return all currently registered bad words.
     *
     * @return list<string>
     */
    public function getBadWords(): array
    {
        return $this->badWords;
    }
}
