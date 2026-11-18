<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\ActionSetsProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('files a plain action made bulk among the record actions, so a bulk mount still runs it over the selection while the table offers no checkboxes', function (): void {
    $this->seed($lamp = new Product('Lamp'), new Product('Vase'));

    $tester = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'peeking']);

    expect($tester->render()->crawler()->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(0)
        ->and($this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'toolbarPushed'])->render()->crawler()->filter(Selectors::RECORD_CHECKBOX))->toHaveCount(2);

    $tester->selectTableRecords([$lamp])->callAction(TestAction::make('peek')->table()->bulk());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['peek read Lamp']);
});

it("runs a group's member mounted by its own name", function (): void {
    $this->createLiveComponent(ActionSetsProductTable::class)->callAction(TestAction::make('print')->table());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['print']);
});

it('evicts a replaced set by identity, so an action another set registered later under the same name keeps running', function (): void {
    $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'shadowed'])->callAction(TestAction::make('note')->table());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['toolbar note']);
});

it('leaves a name no action once the set that last registered it is replaced, although an earlier set still shows its trigger', function (): void {
    $tester = $this->createLiveComponent(ActionSetsProductTable::class, ['setup' => 'evicted']);

    expect($tester->render()->crawler())->toContainElement(Selectors::HEADER_ACTIONS . ' button', 'Note');

    $tester->call('mountAction', ['name' => 'note', 'context' => ['table' => true]])
        ->assertActionNotMounted()
        ->callAction(TestAction::make('export')->table());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['export']);
});
