<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use DateTime;
use DateTimeImmutable;
use LogicException;
use ReflectionProperty;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\DateArticle;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\DateTimeStateCast;

/*
 * The picker resolves its write-side class the same way requirement is derived —
 * class and path both from the nearest node binding a record of its own — so a
 * section that re-roots its subtree decides the cast for the pickers inside it.
 */

beforeEach(function (): void {
    // Boot before the first make(): the resolver's static instance is what makes a
    // picker container-built, and with it its reader the injected service rather
    // than the container-less fallback.
    $this::getContainer();
});

/**
 * The write-side class the picker resolved, read back off the cast it appends,
 * which is where that resolution is observable from outside the component.
 */
function framedPickerObjectClass(DateTimePicker $field): ?string
{
    $cast = array_last($field->getDefaultStateCasts());

    if (! $cast instanceof DateTimeStateCast) {
        throw new LogicException('The picker appended no date cast to read a write-side class from.');
    }

    return new ReflectionProperty($cast, 'objectClass')->getValue($cast);
}

it('resolves the write-side class against the record the nearest frame binds', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('dates')
                ->statePath('dates')
                ->record(DateArticle::class)
                ->components([DateTimePicker::make('publishedAt'), DateTimePicker::make('reviewedAt')]),
        ]);

    expect(framedPickerObjectClass($schema->getComponentByStatePath('data.dates.publishedAt')))->toBe(DateTimeImmutable::class)
        ->and(framedPickerObjectClass($schema->getComponentByStatePath('data.dates.reviewedAt')))->toBe(DateTime::class);
});

it('keeps resolving from the root for a picker outside the frame', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(DateArticle::class)
        ->components([
            DateTimePicker::make('publishedAt'),
            Section::make('meta')
                ->statePath('meta')
                ->record(Article::class)
                ->components([DateTimePicker::make('publishedAt')]),
        ]);

    expect(framedPickerObjectClass($schema->getComponentByStatePath('data.publishedAt')))->toBe(DateTimeImmutable::class)
        ->and(framedPickerObjectClass($schema->getComponentByStatePath('data.meta.publishedAt')))->toBeNull();
});

it('resolves no write-side class for a picker whose path escapes the frame', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->record(DateArticle::class)
        ->components([DateTimePicker::make('publishedAt')]);

    $picker = $schema->getComponents()[0];

    // Memoise the picker's absolute path before the frame has one of its own, so
    // the two no longer nest — the shape the frame path's null arm exists for.
    $picker->getStatePath();

    $schema->statePath('data');

    expect($picker->getStatePath())->toBe('publishedAt')
        ->and($schema->getStatePath())->toBe('data')
        ->and(framedPickerObjectClass($picker))->toBeNull();
});
