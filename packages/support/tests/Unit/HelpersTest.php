<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit;

use ArrayObject;
use Countable;
use DateTimeImmutable;
use DateTimeZone;
use stdClass;
use Stringable;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\diff_for_humans;
use function Sylament\Support\except;
use function Sylament\Support\filled;
use function Sylament\Support\get_record_label;
use function Sylament\Support\only;
use function Sylament\Support\plural;
use function Sylament\Support\sanitize_css_color;
use function Sylament\Support\sanitize_url;
use function Sylament\Support\wrap;

it('treats valueless input as blank', function (mixed $value): void {
    expect(blank($value))->toBeTrue()
        ->and(filled($value))->toBeFalse();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace string' => ['   '],
    'unicode whitespace' => ["\u{00A0}\u{2007}"],
    'whitespace-only Stringable' => [new class implements Stringable {
        public function __toString(): string
        {
            return '  ';
        }
    }],
    'empty array' => [[]],
    'zero-count Countable' => [new ArrayObject()],
    'zero-count Countable with a debug __toString' => [new class implements Countable, Stringable {
        public function count(): int
        {
            return 0;
        }

        public function __toString(): string
        {
            return self::class . '@hash';
        }
    }],
]);

it('treats meaningful values as filled', function (mixed $value): void {
    expect(filled($value))->toBeTrue()
        ->and(blank($value))->toBeFalse();
})->with([
    'text' => ['Heading'],
    'zero int' => [0],
    'zero float' => [0.0],
    'zero string' => ['0'],
    'false' => [false],
    'Markup with content' => [new Markup('<em>Hi</em>', 'UTF-8')],
    'non-empty array' => [[0]],
    'non-empty Countable' => [new ArrayObject([1])],
    'plain object' => [new stdClass()],
]);

it('gives a Markup text semantics despite it being Countable', function (): void {
    // Twig\Markup counts its characters, so the count-first order every other
    // Countable gets (Doctrine-collection parity with Laravel) would report a
    // whitespace-only Markup as filled; it must keep the text semantics
    // Filament's Countable-free HtmlString gets.
    expect(blank(new Markup('   ', 'UTF-8')))->toBeTrue();
});

it('wraps a value into an array the way Arr::wrap does', function (): void {
    expect(wrap(null))->toBe([])
        ->and(wrap('a'))->toBe(['a'])
        ->and(wrap(false))->toBe([false])
        ->and(wrap(['x' => 1]))->toBe(['x' => 1]);
});

it("sanitizes urls the way Filament's Str::sanitizeUrl does", function (?string $url, ?string $expected): void {
    expect(sanitize_url($url))->toBe($expected);
})->with([
    'blank' => [null, null],
    'empty string' => ['', null],
    'http' => ['https://example.com/a?b=c', 'https://example.com/a?b=c'],
    'relative' => ['/uploads/x.png', '/uploads/x.png'],
    'javascript' => ['javascript:alert(1)', null],
    'entity-encoded scheme' => ['&#106;avascript:alert(1)', null],
    'hex-entity-encoded scheme' => ['&#x6a;avascript:alert(1)', null],
    'percent tab' => ['https://example.com/%09x', null],
    'raw control' => ["https://example.com/\x01", null],
    'raw space' => ['https://example.com/a b', null],
    'uppercase scheme' => ['HTTPS://example.com', 'HTTPS://example.com'],
    'data' => ['data:text/html,x', null],
]);

it('honours a custom scheme allow-list', function (): void {
    expect(sanitize_url('mailto:a@b.c', ['mailto']))->toBe('mailto:a@b.c')
        ->and(sanitize_url('https://example.com', ['mailto']))->toBeNull();
});

it('derives a record label from a class basename the way the resource does', function (): void {
    expect(get_record_label('App\\Entity\\BlogPost'))->toBe('blog post')
        ->and(get_record_label('Article'))->toBe('article')
        ->and(get_record_label('App\\Entity\\PDFDocument'))->toBe('pdf document');
});

it('keeps only the named keys', function (): void {
    expect(only(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'c']))->toBe(['a' => 1, 'c' => 3])
        ->and(only(['a' => 1], ['missing']))->toBe([]);
});

it('drops the named keys', function (): void {
    expect(except(['a' => 1, 'b' => 2, 'c' => 3], ['b']))->toBe(['a' => 1, 'c' => 3])
        ->and(except(['a' => 1], []))->toBe(['a' => 1]);
});

it('pluralises the last word of a label', function (string $label, string $expected): void {
    expect(plural($label))->toBe($expected);
})->with([
    ['article', 'articles'],
    ['blog post', 'blog posts'],
    ['category', 'categories'],
    ['record', 'records'],
    ['', ''],
]);

it('accepts a CSS colour the browser can only read as a colour', function (string $color, string $expected): void {
    expect(sanitize_css_color($color))->toBe($expected);
})->with([
    'three-digit hex' => ['#fff', '#fff'],
    'four-digit hex' => ['#fffa', '#fffa'],
    'six-digit hex' => ['#FF00aa', '#FF00aa'],
    'eight-digit hex' => ['#ff00aa80', '#ff00aa80'],
    'keyword' => ['red', 'red'],
    'transparent' => ['transparent', 'transparent'],
    'rgb notation' => ['rgb(0,0,0)', 'rgb(0,0,0)'],
    'rgba notation with spaces' => ['rgba(255, 0, 0, 0.5)', 'rgba(255, 0, 0, 0.5)'],
    'hsl percentages' => ['hsl(120, 50%, 50%)', 'hsl(120, 50%, 50%)'],
    'oklch notation' => ['oklch(62.7% 0.194 149.214)', 'oklch(62.7% 0.194 149.214)'],
    'color() notation' => ['color(display-p3 1 0 0)', 'color(display-p3 1 0 0)'],
    'surrounding whitespace is trimmed' => ['  red  ', 'red'],
]);

it('refuses a CSS colour that could break out of the declaration', function (?string $color): void {
    expect(sanitize_css_color($color))->toBeNull();
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ['   '],
    'declaration injection' => ['red;color:blue'],
    'url() is not a colour notation' => ['url(x)'],
    'nested function call' => ['rgb(var(--x))'],
    'a quote inside the parens' => ['rgb(0,0,0")'],
    'a colon inside the parens' => ['rgb(0:0:0)'],
    'a semicolon inside the parens' => ['rgb(0,0,0);position:fixed'],
    'an unclosed notation' => ['rgb(0,0,0'],
    'two-digit hex' => ['#ff'],
    'five-digit hex' => ['#fffff'],
    'seven-digit hex' => ['#fffffff'],
    'non-hex characters' => ['#gggggg'],
    'a keyword with a digit' => ['red1'],
    'a keyword with a dash' => ['light-dark'],
]);

it('humanises a difference down to its first non-zero unit', function (string $date, string $expected): void {
    $now = new DateTimeImmutable('2026-09-02 12:00:00', new DateTimeZone('UTC'));

    expect(diff_for_humans(new DateTimeImmutable($date, new DateTimeZone('UTC')), $now))->toBe($expected);
})->with([
    'the same instant' => ['2026-09-02 12:00:00', '0 seconds ago'],
    'one second' => ['2026-09-02 11:59:59', '1 second ago'],
    'no rounding up to a minute' => ['2026-09-02 11:59:13', '47 seconds ago'],
    'one minute' => ['2026-09-02 11:59:00', '1 minute ago'],
    'minutes, seconds dropped' => ['2026-09-02 11:57:30', '2 minutes ago'],
    'hours' => ['2026-09-02 09:00:00', '3 hours ago'],
    'days below a week' => ['2026-08-30 12:00:00', '3 days ago'],
    'exactly a week' => ['2026-08-26 12:00:00', '1 week ago'],
    'a week and six days' => ['2026-08-20 12:00:00', '1 week ago'],
    'two weeks' => ['2026-08-19 12:00:00', '2 weeks ago'],
    'a month' => ['2026-08-02 12:00:00', '1 month ago'],
    'months' => ['2026-06-02 12:00:00', '3 months ago'],
    'a year' => ['2025-09-02 12:00:00', '1 year ago'],
    'years' => ['2024-09-02 12:00:00', '2 years ago'],
    'a future instant' => ['2026-09-02 14:00:00', '2 hours from now'],
    'a future day' => ['2026-09-05 12:00:00', '3 days from now'],
    'a future year' => ['2027-09-02 12:00:00', '1 year from now'],
]);

it('humanises across timezones by the absolute instant', function (): void {
    $now = new DateTimeImmutable('2026-09-02 12:00:00', new DateTimeZone('UTC'));
    $date = new DateTimeImmutable('2026-09-02 13:00:00', new DateTimeZone('Europe/Bratislava'));

    expect(diff_for_humans($date, $now))->toBe('1 hour ago');
});
