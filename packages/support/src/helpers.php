<?php

declare(strict_types=1);

namespace Sylament\Support;

use Countable;
use Stringable;
use Twig\Markup;

/**
 * @phpstan-assert-if-false !null $value
 */
function blank(mixed $value): bool
{
    if ($value === null) {
        return true;
    }

    if (is_string($value)) {
        return mb_trim($value) === '';
    }

    if (is_numeric($value) || is_bool($value)) {
        return false;
    }

    if ($value instanceof Markup) {
        return mb_trim((string) $value) === '';
    }

    if ($value instanceof Countable) {
        return count($value) === 0;
    }

    if ($value instanceof Stringable) {
        return mb_trim((string) $value) === '';
    }

    return $value === [];
}

/**
 * @phpstan-assert-if-true !null $value
 */
function filled(mixed $value): bool
{
    return !blank($value);
}

/**
 * Null for a blank URL, one carrying raw whitespace or control characters, one hiding a control
 * character behind an entity or a percent escape, or one whose scheme is outside the allow-list;
 * the original string otherwise. A relative URL has no scheme and passes.
 *
 * @param list<string> $allowedSchemes
 */
function sanitize_url(?string $url, array $allowedSchemes = ['http', 'https']): ?string
{
    if (blank($url)) {
        return null;
    }

    // Reject URLs containing raw whitespace or control characters. Legitimate URLs
    // percent-encode these; a raw byte here is either a typo or an obfuscation attempt.
    if (preg_match('/[\x00-\x20\x7F]/', $url) === 1) {
        return null;
    }

    // Predict the scheme the browser will see after HTML-decoding the attribute value.
    // ASCII numeric entities are pre-decoded here because `html_entity_decode(ENT_HTML5)`
    // replaces "forbidden" C0 control character entities (e.g. `&#0;`) with U+FFFD, which
    // would mask them from the control-character strip below.
    $decoded = preg_replace_callback(
        '/&#(?:x([0-9a-f]+)|([0-9]+));?/i',
        /** @param array<int, string> $match */
        static function (array $match): string {
            $code = $match[1] === '' ? (int) $match[2] : (int) hexdec($match[1]);

            return $code >= 0 && $code <= 127 ? chr($code) : $match[0];
        },
        $url,
    );

    if ($decoded === null) {
        return null;
    }

    $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Reject if either the HTML-decoded form or its percent-decoded form contains control
    // characters — this catches `%09`-style obfuscation that would be dangerous if the URL is
    // later decoded by other code.
    if (preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1 || preg_match('/[\x00-\x1F\x7F]/', rawurldecode($decoded)) === 1) {
        return null;
    }

    // The scheme check uses the HTML-decoded form only — the browser does not percent-decode
    // the scheme, so a `%6A` literally in the scheme position is not dangerous.
    if (
        preg_match('/^([a-z][a-z0-9+\-.]*):/i', $decoded, $matches) === 1
        && !in_array(mb_strtolower($matches[1]), array_map(mb_strtolower(...), $allowedSchemes), strict: true)
    ) {
        return null;
    }

    return $url;
}

/**
 * @template TValue
 *
 * @param array<TValue>|TValue|null $value
 *
 * @return array<TValue>
 */
function wrap(mixed $value): array
{
    if ($value === null) {
        return [];
    }

    return is_array($value) ? $value : [$value];
}
