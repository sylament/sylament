<?php

declare(strict_types=1);

namespace Sylament\Support;

use Countable;
use DateTimeInterface;
use Stringable;
use Symfony\Component\String\Inflector\EnglishInflector;
use Twig\Markup;

use function Symfony\Component\String\u;

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
 * Null unless the string is a value the browser can only read as a colour — a hex
 * triplet/quadruplet, a bare keyword, or one of the functional notations with the CSS
 * metacharacters forbidden inside its parentheses; the trimmed string otherwise.
 */
function sanitize_css_color(?string $color): ?string
{
    if (blank($color)) {
        return null;
    }

    $color = mb_trim($color);

    // Accept hex colors: `#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa`.
    if (preg_match('/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color) === 1) {
        return $color;
    }

    if (preg_match('/^[a-z]+$/i', $color) === 1) {
        return $color;
    }

    // Security: allow functional color notations, but forbid the CSS metacharacters
    // `( ) ; : " '` inside the parentheses so the value cannot break out of the
    // `background-color` declaration to inject additional properties (e.g.
    // `red;position:fixed;background-image:url(//attacker)`) or extra function calls.
    if (preg_match('/^(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color)\([^();:"\']*\)$/i', $color) === 1) {
        return $color;
    }

    return null;
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

/**
 * Filament's `get_model_label()` under the record vocabulary — the class
 * basename, kebab-cased, dashes to spaces: `App\Entity\BlogPost` → `blog post`.
 */
function get_record_label(string $class): string
{
    return u($class)->afterLast('\\')->kebab()->replace('-', ' ')->toString();
}

/**
 * Laravel's `Str::plural`, as far as the port needs it: the last word of
 * `$value` inflected through symfony/string's `EnglishInflector`, taking its
 * last candidate, the other words untouched — `blog post` → `blog posts`.
 * English only: i18n is deferred, so Filament's `locale_has_pluralization()`
 * guard has no port yet.
 */
function plural(string $value): string
{
    $words = explode(' ', $value);
    $lastWord = array_key_last($words);
    $candidates = new EnglishInflector()->pluralize($words[$lastWord]);
    $lastCandidate = array_key_last($candidates);

    if ($lastCandidate !== null) {
        $words[$lastWord] = $candidates[$lastCandidate];
    }

    return implode(' ', $words);
}

/**
 * Laravel's `Arr::only`.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @param array<TKey, TValue> $array
 * @param array<array-key> $keys
 *
 * @return array<TKey, TValue>
 */
function only(array $array, array $keys): array
{
    return array_intersect_key($array, array_flip($keys));
}

/**
 * Laravel's `Arr::except`.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @param array<TKey, TValue> $array
 * @param array<array-key> $keys
 *
 * @return array<TKey, TValue>
 */
function except(array $array, array $keys): array
{
    return array_diff_key($array, array_flip($keys));
}

/**
 * Carbon's `diffForHumans()` at its one-part default: the first non-zero unit of the
 * difference between `$date` and `$now`, English-pluralised, read as `":time ago"`
 * when `$date` is at or before `$now` and `":time from now"` when it is after. No
 * thresholds and no rounding — 47 seconds is `47 seconds ago`, never `a minute ago`.
 */
function diff_for_humans(DateTimeInterface $date, DateTimeInterface $now): string
{
    $difference = $date->diff($now);

    $counts = [
        'year' => $difference->y,
        'month' => $difference->m,
        'week' => intdiv($difference->d, 7),
        'day' => $difference->d % 7,
        'hour' => $difference->h,
        'minute' => $difference->i,
        'second' => $difference->s,
    ];

    $time = '0 seconds';

    foreach ($counts as $unit => $count) {
        if ($count > 0) {
            $time = $count . ' ' . ($count === 1 ? $unit : $unit . 's');

            break;
        }
    }

    return $difference->invert === 1 ? $time . ' from now' : $time . ' ago';
}
