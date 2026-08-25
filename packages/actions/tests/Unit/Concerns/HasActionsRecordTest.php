<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use DateTimeImmutable;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Concerns\RecordResolutionFixture;
use Sylament\Actions\Tests\Fixtures\Twig\Components\DefaultRecordHost;

use function Sylament\Support\get_record_label;

it('throws when a recordKey is present but no resolveRecordUsing callback is registered', function (): void {
    $fixture = new RecordResolutionFixture();
    $fixture->mountedActions = [[
        'name' => 'x',
        'arguments' => [],
        'context' => ['recordKey' => 1],
    ]];

    expect(fn (): mixed => $fixture->exposeMountedActionRecord())->toThrow(LogicException::class);
});

it('resolves the record when resolveRecordUsing is registered', function (): void {
    $fixture = new RecordResolutionFixture();
    $fixture->resolveRecordUsing(fn (mixed $key): string => "row-{$key}");
    $fixture->mountedActions = [[
        'name' => 'x',
        'arguments' => [],
        'context' => ['recordKey' => 1],
    ]];

    expect($fixture->exposeMountedActionRecord())->toBe('row-1');
});

it('returns null when the context has no recordKey', function (): void {
    $fixture = new RecordResolutionFixture();
    $fixture->mountedActions = [[
        'name' => 'x',
        'arguments' => [],
        'context' => [],
    ]];

    expect($fixture->exposeMountedActionRecord())->toBeNull();
});

it('falls through to the host record, class, label and title, and only with a default', function (): void {
    $host = new DefaultRecordHost();
    $action = Action::make('x')->liveComponent($host);

    expect($action->getRecord())->toBe($host->record)
        ->and($action->getRecord(withDefault: false))->toBeNull()
        ->and($action->getRecordClass())->toBe($host->record::class)
        ->and($action->getRecordLabel())->toBe('thing')
        ->and($action->getTitleCaseRecordLabel())->toBe('Thing')
        ->and($action->getRecordTitle())->toBe('The thing');
});

it('derives the label itself when the host owns the class but answers no label', function (): void {
    $host = new DefaultRecordHost();
    $host->recordLabel = null;

    $action = Action::make('x')->liveComponent($host);

    expect($action->getRecordLabel())->toBe(get_record_label($host->record::class));
});

it('ignores the host label for a class that is not the host default class', function (): void {
    $action = Action::make('x')
        ->liveComponent(new DefaultRecordHost())
        ->recordClass(DateTimeImmutable::class);

    expect($action->getRecordLabel())->toBe('date time immutable');
});

it('refuses the host record class without a default', function (): void {
    $action = Action::make('x')->liveComponent(new DefaultRecordHost());

    expect($action->getRecordClass(withDefault: false))->toBeNull();
});

it('defaults the relationship hook to null', function (): void {
    expect(new RecordResolutionFixture()->getDefaultActionRelationship(Action::make('x')))->toBeNull();
});
