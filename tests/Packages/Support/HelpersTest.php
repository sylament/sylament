<?php

declare(strict_types=1);

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

it('reads a value as blank or filled by its class', function (mixed $value, bool $blank): void {
    expect(blank($value))->toBe($blank)
        ->and(filled($value))->toBe(!$blank);
})->with([
    'null' => [null, true],
    'an empty string' => ['', true],
    'a whitespace-only string' => [" \t\n", true],
    'a string of unicode whitespace' => ["\u{00A0}\u{2003}", true],
    'a non-empty string' => ['0', false],
    'zero' => [0, false],
    'false' => [false, false],
    'an empty array' => [[], true],
    'a non-empty array' => [[null], false],
    'an empty Countable' => [new ArrayObject(), true],
    'a whitespace-only Markup, despite its count' => [new Markup('  ', 'UTF-8'), true],
    'a non-empty Markup' => [new Markup('<b>Bold</b>', 'UTF-8'), false],
    'a whitespace-only Stringable' => [new class implements Stringable {
        public function __toString(): string
        {
            return ' ';
        }
    }, true],
    'an empty Countable that is also Stringable, by its count' => [new class implements Countable, Stringable {
        public function count(): int
        {
            return 0;
        }

        public function __toString(): string
        {
            return 'text';
        }
    }, true],
]);

it('wraps null into an empty array and a scalar or object into a one-element array, passing an array through', function (): void {
    $object = new stdClass();

    expect(wrap(null))->toBe([])
        ->and(wrap(['a' => 1]))->toBe(['a' => 1])
        ->and(wrap('a'))->toBe(['a'])
        ->and(wrap($object))->toBe([$object]);
});

it('refuses a URL the browser could read as something other than it shows', function (mixed ...$arguments): void {
    expect(sanitize_url(...$arguments))->toBeNull();
})->with([
    'a blank URL' => ['  '],
    'raw whitespace' => ['https://example.test/a b'],
    'a raw control character' => ["https://example.test/\x01"],
    'a control character behind an entity' => ['java&#x09;script:alert(1)'],
    'a control character behind a percent escape' => ['https://example.test/%00'],
    'a scheme outside the default list' => ['javascript:alert(1)'],
    'a scheme hidden behind an entity' => ['&#106;avascript:alert(1)'],
    "a default scheme outside the caller's list" => ['https://example.test', ['mailto']],
]);

it('returns a relative URL, or an absolute one whose scheme is allowed in any case, unchanged', function (): void {
    expect(sanitize_url('/articles/1?page=2'))->toBe('/articles/1?page=2')
        ->and(sanitize_url('HTTPS://example.test/a'))->toBe('HTTPS://example.test/a')
        ->and(sanitize_url('mailto:team@example.test', ['mailto']))->toBe('mailto:team@example.test');
});

it('answers a CSS colour trimmed, or null when the value could carry anything else', function (?string $color, ?string $sanitized): void {
    expect(sanitize_css_color($color))->toBe($sanitized);
})->with([
    'a hex colour' => ['#0f172a', '#0f172a'],
    'a keyword' => ['rebeccapurple', 'rebeccapurple'],
    'a functional notation' => ['oklch(0.5 0.1 20 / 50%)', 'oklch(0.5 0.1 20 / 50%)'],
    'a colour padded with unicode whitespace' => ["\u{00A0}#fff\u{2003}", '#fff'],
    'a blank value' => [' ', null],
    'a metacharacter inside the parentheses' => ['rgb(0 0 0; color: red)', null],
    'a declaration after a keyword' => ['red;position:fixed', null],
]);

it('reads the first non-zero unit of a difference, ago or from now', function (string $date, string $now, string $read): void {
    expect(diff_for_humans(new DateTimeImmutable($date), new DateTimeImmutable($now)))->toBe($read);
})->with([
    'seconds, never rounded up' => ['2024-01-01 12:00:00', '2024-01-01 12:00:47', '47 seconds ago'],
    'one unit, singular' => ['2024-01-01 11:00:00', '2024-01-01 12:00:30', '1 hour ago'],
    'days as weeks from seven' => ['2024-01-01', '2024-01-11', '1 week ago'],
    'the largest unit first' => ['2022-11-01', '2024-01-01', '1 year ago'],
    'a future date' => ['2024-01-03', '2024-01-01', '2 days from now'],
    'no difference' => ['2024-01-01', '2024-01-01', '0 seconds ago'],
]);

it('labels a record class by its basename in words, an acronym kept whole', function (): void {
    expect(get_record_label('App\Entity\BlogPost'))->toBe('blog post')
        ->and(get_record_label('App\Entity\PDFDocument'))->toBe('pdf document');
});

it('keeps only the listed keys', function (): void {
    expect(only(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'c', 'missing']))->toBe(['a' => 1, 'c' => 3]);
});

it('drops the listed keys', function (): void {
    expect(except(['a' => 1, 'b' => 2, 'c' => 3], ['b', 'missing']))->toBe(['a' => 1, 'c' => 3]);
});

it("inflects only a phrase's last word, by the inflector's last candidate", function (): void {
    expect(plural('blog post'))->toBe('blog posts')
        ->and(plural('sales person'))->toBe('sales people');
});
