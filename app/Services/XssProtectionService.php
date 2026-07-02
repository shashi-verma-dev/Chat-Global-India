<?php

namespace App\Services;

class XssProtectionService
{
    /**
     * HTML tags that are explicitly allowed to pass through.
     *
     * An empty array means ALL tags are stripped.
     * Add tags here only if your UI renders HTML (it currently does not).
     *
     * @var list<string>
     */
    private array $allowedTags = [];

    /**
     * Sanitise a raw string for safe storage and display.
     *
     * Pipeline applied in order:
     *   1. strip_tags      — removes every HTML/XML tag not in $allowedTags.
     *   2. htmlspecialchars — encodes remaining special characters (&, <, >, ", ').
     *   3. trim            — strips leading/trailing whitespace.
     *
     * Because the chat Blade view renders messages with {{ }}, Laravel already
     * HTML-encodes output. This service provides a defence-in-depth layer so
     * the stored database value is also clean.
     *
     * @param  string $input  Raw user-supplied text.
     * @return string         Sanitised, safe-to-store string.
     */
    public function sanitise(string $input): string
    {
        // 1. Remove disallowed HTML tags.
        $safe = strip_tags($input, $this->allowedTags);

        // 2. Encode special HTML characters.
        $safe = htmlspecialchars($safe, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 3. Trim whitespace.
        return trim($safe);
    }

    /**
     * Alias of sanitise() for developers who prefer American spelling.
     *
     * @param  string $input
     * @return string
     */
    public function sanitize(string $input): string
    {
        return $this->sanitise($input);
    }

    /**
     * Check whether the raw input contains any HTML tags.
     *
     * Useful for flagging suspicious input before deciding to block or log it.
     *
     * @param  string $input
     * @return bool
     */
    public function containsHtml(string $input): bool
    {
        return $input !== strip_tags($input);
    }

    /**
     * Check whether the input contains a common XSS pattern
     * (script tags, event handlers, javascript: URIs, etc.).
     *
     * This is a heuristic check — it does not replace strip_tags/htmlspecialchars.
     *
     * @param  string $input
     * @return bool
     */
    public function containsXssPattern(string $input): bool
    {
        $patterns = [
            '/<\s*script/i',            // <script>
            '/javascript\s*:/i',        // javascript: URI
            '/on\w+\s*=/i',             // onerror=, onclick=, etc.
            '/<\s*iframe/i',            // <iframe>
            '/<\s*object/i',            // <object>
            '/<\s*embed/i',             // <embed>
            '/expression\s*\(/i',       // IE CSS expression()
            '/vbscript\s*:/i',          // VBScript URI
            '/data\s*:\s*text\/html/i', // data:text/html URI
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the list of allowed HTML tags that should NOT be stripped.
     *
     * @param  list<string> $tags  e.g. ['<b>', '<i>', '<em>']
     * @return void
     */
    public function setAllowedTags(array $tags): void
    {
        $this->allowedTags = $tags;
    }
}
