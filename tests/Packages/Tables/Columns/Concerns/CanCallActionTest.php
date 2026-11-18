<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\ColumnActionTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('calls the host method a string action names with the clicked row, an array row included', function (): void {
    $tester = $this->createLiveComponent(ColumnActionTable::class);
    $wrapper = $tester->render()->crawler()->filter(Selectors::cell('lamp', 'name') . ' ' . Selectors::CELL_WRAPPER);

    expect($wrapper->nodeName())->toBe('button')
        ->and($wrapper->attr('data-live-action-param'))->toBe('callTableColumnAction')
        ->and($wrapper->attr('data-live-name-param'))->toBe('name')
        ->and($wrapper->attr('data-live-record-key-param'))->toBe('lamp');

    $tester->callTableColumnAction('name', 'lamp');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive Lamp']);
});

it('calls a closure action with the clicked row', function (): void {
    $tester = $this->createLiveComponent(ColumnActionTable::class);
    $wrapper = $tester->render()->crawler()->filter(Selectors::cell('lamp', 'note') . ' ' . Selectors::CELL_WRAPPER);

    expect($wrapper->nodeName())->toBe('button')
        ->and($wrapper->attr('data-live-action-param'))->toBe('callTableColumnAction')
        ->and($wrapper->attr('data-live-name-param'))->toBe('note')
        ->and($wrapper->attr('data-live-record-key-param'))->toBe('lamp');

    $tester->callTableColumnAction('note', 'lamp');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['note Lamp']);
});

it('mounts an action given as an action on the clicked row', function (): void {
    $tester = $this->createLiveComponent(ColumnActionTable::class);
    $wrapper = $tester->render()->crawler()->filter(Selectors::cell('lamp', 'sku') . ' ' . Selectors::CELL_WRAPPER);

    expect($wrapper->nodeName())->toBe('button')
        ->and($wrapper->attr('data-live-action-param'))->toBe('mountAction')
        ->and($wrapper->attr('data-live-name-param'))->toBe('open')
        ->and(json_decode((string) $wrapper->attr('data-live-context-param'), true))->toBe(['recordKey' => 'lamp', 'table' => true]);

    $tester->callAction(TestAction::make('open')->table('lamp'));

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['open Lamp']);
});
