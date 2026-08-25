<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit;

use ArrayObject;
use Countable;
use stdClass;
use Stringable;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\except;
use function Sylament\Support\filled;
use function Sylament\Support\get_record_label;
use function Sylament\Support\only;
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
