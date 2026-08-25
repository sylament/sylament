<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use DateTimeImmutable;
use LogicException;
use stdClass;
use Stringable;
use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Concerns\InteractsWithRecordFixture;

use function Sylament\Support\get_record_label;

it('has no record by default', function (): void {
    expect(new InteractsWithRecordFixture()->getRecord())->toBeNull();
});

it('round-trips a record value', function (): void {
    expect(new InteractsWithRecordFixture()->record('Ada')->getRecord())->toBe('Ada');
});

it('evaluates a record closure', function (): void {
    expect(new InteractsWithRecordFixture()->record(fn (): string => 'Grace')->getRecord())->toBe('Grace');
});

it('reports record presence', function (): void {
    expect(new InteractsWithRecordFixture()->hasRecord())->toBeFalse()
        ->and(new InteractsWithRecordFixture()->record('Ada')->hasRecord())->toBeTrue();
});

it('resolves record keys from arrays, ids and Stringable ids', function (): void {
    $action = Action::make('edit');

    expect($action->resolveRecordKey(['__key' => 5]))->toBe('5')
        ->and($action->resolveRecordKey(new class {
            public int $id = 7;
        }))->toBe('7')
        ->and($action->resolveRecordKey(new class {
            public function getId(): string
            {
                return 'abc';
            }
        }))->toBe('abc')
        ->and($action->resolveRecordKey(new class {
            public function getId(): Stringable
            {
                return new class implements Stringable {
                    public function __toString(): string
                    {
                        return 'ada';
                    }
                };
            }
        }))->toBe('ada');
});

it('resolves no record key from an unreadable, blank or non-record value', function (): void {
    $action = Action::make('edit');

    expect($action->resolveRecordKey(new class {}))->toBeNull()
        ->and($action->resolveRecordKey(new class {
            public string $id = '';
        }))->toBeNull()
        ->and($action->resolveRecordKey(null))->toBeNull()
        ->and($action->resolveRecordKey('scalar'))->toBeNull();
});

it('throws for an array record without the key entry', function (): void {
    Action::make('edit')->resolveRecordKey(['title' => 'x']);
})->throws(LogicException::class, '[__key]');

it("answers a custom record class, else the bound record's class", function (): void {
    $fixture = new InteractsWithRecordFixture();

    expect($fixture->getRecordClass())->toBeNull()
        ->and($fixture->record(new stdClass())->getRecordClass())->toBe(stdClass::class)
        ->and($fixture->recordClass(DateTimeImmutable::class)->getRecordClass())->toBe(DateTimeImmutable::class)
        ->and($fixture->recordClass(fn (): string => stdClass::class)->getCustomRecordClass())->toBe(stdClass::class);
});

it('derives the record label from the class and title-cases it', function (): void {
    $fixture = new InteractsWithRecordFixture();

    expect($fixture->getRecordLabel())->toBeNull()
        ->and($fixture->recordClass('App\\Entity\\BlogPost')->getRecordLabel())->toBe('blog post')
        ->and($fixture->getTitleCaseRecordLabel())->toBe('Blog Post')
        ->and($fixture->recordLabel('entry')->getRecordLabel())->toBe('entry');
});

it('resolves the record title from a closure, then an attribute, then the label', function (): void {
    $record = new class {
        public string $name = 'Ada';
    };
    $fixture = new InteractsWithRecordFixture()->record($record);

    expect($fixture->getRecordTitle())->toBe(get_record_label($record::class))
        ->and($fixture->recordTitleAttribute('name')->getRecordTitle())->toBe('Ada')
        ->and($fixture->hasCustomRecordTitleAttribute())->toBeTrue()
        ->and($fixture->getRecordTitleAttribute())->toBe('name')
        ->and($fixture->recordTitle(fn (object $record): string => mb_strtoupper($record->name))->getRecordTitle())->toBe('ADA')
        ->and($fixture->hasCustomRecordTitle())->toBeTrue()
        ->and($fixture->getRecordTitle(new class {
            public string $name = 'Grace';
        }))->toBe('GRACE');
});

it('never runs the custom title without a record', function (): void {
    $fixture = new InteractsWithRecordFixture()->recordTitle(fn (object $record): string => $record->name);

    expect($fixture->getRecordTitle())->toBeNull();
});

it('drops an ambient record that is not of the custom record class', function (): void {
    $fixture = new InteractsWithRecordFixture()->record(new stdClass())->recordClass(DateTimeImmutable::class);

    expect($fixture->getRecord())->toBeNull();
});

it('derives the plural record label from the singular and title-cases it', function (): void {
    $fixture = new InteractsWithRecordFixture();

    expect($fixture->getPluralRecordLabel())->toBeNull()
        ->and($fixture->getCustomPluralRecordLabel())->toBeNull()
        ->and($fixture->getTitleCasePluralRecordLabel())->toBeNull()
        ->and($fixture->recordClass('App\\Entity\\BlogPost')->getPluralRecordLabel())->toBe('blog posts')
        ->and($fixture->getTitleCasePluralRecordLabel())->toBe('Blog Posts')
        ->and($fixture->pluralRecordLabel('people')->getCustomPluralRecordLabel())->toBe('people')
        ->and($fixture->getPluralRecordLabel())->toBe('people');
});
