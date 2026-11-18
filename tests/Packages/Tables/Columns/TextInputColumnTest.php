<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\EditableCellLooksTable;
use Sylament\Tests\Fixtures\Tables\EditableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

describe('the input', function (): void {
    it("renders support's input wrapper around forms' input, holding the state, named by its label and titled by its tooltip, with none of a field's id, name or model", function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $name = Selectors::cell('north', 'name');

        expect($crawler)->toContainElement($name . ' ' . Selectors::TEXT_INPUT_COLUMN . '[title="Rename the jar"] ' . SupportSelectors::INPUT_WRAPPER . ':not(' . SupportSelectors::DISABLED . '):not(' . SupportSelectors::INVALID . ') ' . FormsSelectors::INPUT . '[value="Plum"][aria-label="Name"]:not([disabled]):not([aria-invalid])')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'locked') . ' ' . SupportSelectors::INPUT_WRAPPER . SupportSelectors::DISABLED . ' ' . FormsSelectors::INPUT . '[value="Sealed"][disabled]')
            ->and($crawler->filter($name . ' ' . FormsSelectors::INPUT . '[id], ' . $name . ' ' . FormsSelectors::INPUT . '[name], ' . $name . ' ' . FormsSelectors::INPUT . '[data-model]'))->toHaveCount(0);
    });

    it('types its input as text unless told otherwise', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'name') . ' input[type="text"]')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'email') . ' input[type="email"]');
    });

    it('renders the input mode it is given, and none unset', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'email') . ' input[inputmode="email"]')
            ->and($crawler->filter(Selectors::cell('north', 'name') . ' input[inputmode]'))->toHaveCount(0);
    });

    it('renders the step it is given', function (): void {
        expect($this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler())
            ->toContainElement(Selectors::cell('north', 'weight') . ' input[type="number"][step="5"]');
    });

    it('shows its placeholder in a blank input, which still renders, and none unset', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'email') . ' ' . FormsSelectors::INPUT . '[value=""][placeholder="jar@example.com"]')
            ->and($crawler->filter(Selectors::cell('north', 'name') . ' input[placeholder]'))->toHaveCount(0);
    });

    it('puts its extra input attributes on its input, not its root', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $email = Selectors::cell('north', 'email');

        expect($crawler)->toContainElement($email . ' ' . FormsSelectors::INPUT . '[data-probe="email"]')
            ->and($crawler->filter($email . ' ' . Selectors::TEXT_INPUT_COLUMN . '[data-probe]'))->toHaveCount(0);
    });

    it('publishes its state on its root for the controller, and commits its input on change', function (): void {
        expect($this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler())
            ->toContainElement(Selectors::cell('north', 'name') . ' ' . Selectors::TEXT_INPUT_COLUMN . Selectors::editableCellState('Plum') . '[data-sylament--tables--editable-cell-column-value="name"][data-sylament--tables--editable-cell-record-value="north"] ' . FormsSelectors::INPUT . Selectors::EDITABLE_CONTROL);
    });

    it("paints a refused write under its input, marks the input invalid and echoes the post in it, and rings the wrapper", function (): void {
        $this->seed($jar = new LarderJar('Plum', position: 1));
        $label = Selectors::cell((string) $jar->id, 'label');

        $crawler = $this->createLiveComponent(EditableLarderJarTable::class)
            ->call('updateTableColumnState', ['column' => 'label', 'record' => (string) $jar->id, 'input' => 'A plum far too long'])
            ->render()
            ->crawler();

        expect($crawler)->toContainElement($label . ' ' . Selectors::TEXT_INPUT_COLUMN . ' ' . Selectors::CELL_ERROR, 'Keep the label to 12 characters.')
            ->and($crawler)->toContainElement($label . ' ' . SupportSelectors::INPUT_WRAPPER . SupportSelectors::INVALID . ' ' . FormsSelectors::INPUT . '[aria-invalid="true"][value="A plum far too long"]');
    });

    it('carries inline and its alignment on its root', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'name') . ' ' . Selectors::TEXT_INPUT_COLUMN . '.sy-inline' . SupportSelectors::align('end'))
            ->and($crawler)->toContainElement(Selectors::cell('north', 'email') . ' ' . Selectors::TEXT_INPUT_COLUMN . ':not(.sy-inline)' . SupportSelectors::NO_ALIGN);
    });

    it('keeps its cell out of the record link a clickable row wraps its other cells in', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'label') . ' a' . Selectors::CELL_WRAPPER . '[href="/larder/north"]')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'name') . ' div' . Selectors::CELL_WRAPPER)
            ->and($crawler->filter(Selectors::cell('north', 'name') . ' a' . Selectors::CELL_WRAPPER))->toHaveCount(0);
    });
});

describe('the affixes', function (): void {
    it('renders a prefix and a suffix label in the wrapper, the second argument placing one inline beside the input', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $weight = Selectors::cell('north', 'weight');

        expect($crawler)->toContainElement($weight . ' ' . SupportSelectors::PREFIX . ':not(.sy-inline)', 'kg')
            ->and($crawler)->toContainElement($weight . ' ' . SupportSelectors::INLINE_SUFFIX, 'net')
            ->and($crawler)->toContainElement($weight . ' ' . FormsSelectors::INPUT . '.sy-fo-input-has-inline-suffix:not(.sy-fo-input-has-inline-prefix)');
    });

    it('places a side by the last call on it, a prefix() resetting an earlier inlinePrefix()', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

        expect($crawler)->toContainElement(Selectors::cell('north', 'volume') . ' ' . SupportSelectors::PREFIX . ':not(.sy-inline)', 'l')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'volume') . ' ' . FormsSelectors::INPUT . ':not(.sy-fo-input-has-inline-prefix)')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'depth') . ' ' . SupportSelectors::INLINE_PREFIX, 'cm')
            ->and($crawler)->toContainElement(Selectors::cell('north', 'depth') . ' ' . FormsSelectors::INPUT . '.sy-fo-input-has-inline-prefix');
    });

    it('renders a prefix and a suffix icon in the wrapper, gray unless coloured, placed inline as a label is', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $stock = Selectors::cell('north', 'stock');

        expect($crawler)->toContainElement($stock . ' ' . SupportSelectors::PREFIX . ':not(.sy-inline) svg' . SupportSelectors::GRAY, 'Heart')
            ->and($crawler)->toContainElement($stock . ' ' . SupportSelectors::INLINE_SUFFIX . ' svg' . SupportSelectors::color('success'), 'Star')
            ->and($crawler)->toContainElement($stock . ' ' . FormsSelectors::INPUT . '.sy-fo-input-has-inline-suffix');
    });
});
