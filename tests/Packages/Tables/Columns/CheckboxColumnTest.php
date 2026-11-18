<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\EditableCellLooksTable;
use Sylament\Tests\Fixtures\Tables\EditableFlaggedSampleTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('writes true when its box is ticked and false when it is unticked, and the cell follows the record', function (): void {
    $this->seed($sample = new ColumnKindSample('Rinsed'));
    $cell = Selectors::cell((string) $sample->id, 'flagged');

    $tester = $this->createLiveComponent(EditableFlaggedSampleTable::class)
        ->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => true]);

    expect($this->reload($sample)->flagged)->toBeTrue()
        ->and($tester->render()->crawler())->toContainElement($cell . ' ' . Selectors::editableCellState('1') . ' input[type="checkbox"][checked]');

    $tester->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => false]);

    expect($this->reload($sample)->flagged)->toBeFalse()
        ->and($tester->render()->crawler())->toContainElement($cell . ' ' . Selectors::editableCellState('0') . ' input[type="checkbox"]:not([checked])');
});

it('refuses anything but a boolean, painting the refusal under its box and marking the box invalid', function (): void {
    $this->seed($sample = new ColumnKindSample('Rinsed'));
    $cell = Selectors::cell((string) $sample->id, 'flagged');

    $crawler = $this->createLiveComponent(EditableFlaggedSampleTable::class)
        ->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => 'yes'])
        ->render()
        ->crawler();

    expect($this->reload($sample)->flagged)->toBeFalse()
        ->and($crawler)->toContainElement($cell . ' ' . Selectors::CHECKBOX_COLUMN . ' ' . Selectors::CELL_ERROR, 'This value should be of type bool.')
        ->and($crawler)->toContainElement($cell . ' input[type="checkbox"][aria-invalid="true"]');
});

it('renders its box ticked, unticked or disabled, named by its label without markup and titled by its tooltip', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'ticked') . ' ' . Selectors::CHECKBOX_COLUMN . '[title="Tick when stocked"] input' . SupportSelectors::CHECKBOX . '[type="checkbox"][aria-label="Ticked"][checked]:not([disabled]):not([aria-invalid])')
        ->and($crawler)->toContainElement(Selectors::cell('north', 'unticked') . ' input' . SupportSelectors::CHECKBOX . '[type="checkbox"][aria-label="Unticked jar"]:not([checked])')
        ->and($crawler)->toContainElement(Selectors::cell('north', 'frozen') . ' input' . SupportSelectors::CHECKBOX . '[type="checkbox"][checked][disabled]')
        ->and($crawler->filter(Selectors::cell('north', 'unticked') . ' ' . Selectors::CHECKBOX_COLUMN . '[title]'))->toHaveCount(0);
});

it('publishes its state on its root for the controller, and commits its box on change', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'ticked') . ' ' . Selectors::CHECKBOX_COLUMN . Selectors::editableCellState('1') . Selectors::editableCell('ticked', 'north') . ' input' . Selectors::EDITABLE_CONTROL)
        ->and($crawler)->toContainElement(Selectors::cell('north', 'unticked') . ' ' . Selectors::editableCellState('0'));
});

it('carries inline and its alignment on its root', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'ticked') . ' ' . Selectors::CHECKBOX_COLUMN . SchemasSelectors::INLINE . SupportSelectors::align('center'))
        ->and($crawler)->toContainElement(Selectors::cell('north', 'unticked') . ' ' . Selectors::CHECKBOX_COLUMN . ':not(' . SchemasSelectors::INLINE . ')' . SupportSelectors::NO_ALIGN);
});

it('puts its extra input attributes on its box, not its root', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'ticked') . ' input[type="checkbox"][data-probe="ticked"]')
        ->and($crawler->filter(Selectors::cell('north', 'ticked') . ' ' . Selectors::CHECKBOX_COLUMN . '[data-probe]'))->toHaveCount(0);
});

it('keeps its cell out of the record link a clickable row wraps its other cells in', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'label') . ' a' . Selectors::CELL_WRAPPER . '[href="/larder/north"]')
        ->and($crawler)->toContainElement(Selectors::cell('north', 'ticked') . ' div' . Selectors::CELL_WRAPPER)
        ->and($crawler->filter(Selectors::cell('north', 'ticked') . ' a' . Selectors::CELL_WRAPPER))->toHaveCount(0);
});
