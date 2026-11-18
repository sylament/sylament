<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\EditableCellLooksTable;
use Sylament\Tests\Fixtures\Tables\EditableFlaggedSampleTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('refuses a post its strict boolean rule turns down, painting the refusal under its switch, and writes the JSON boolean the switch posts', function (mixed $input): void {
    $this->seed($sample = new ColumnKindSample('Rinsed'));
    $cell = Selectors::cell((string) $sample->id, 'flagged');

    $tester = $this->createLiveComponent(EditableFlaggedSampleTable::class, ['control' => 'toggle'])
        ->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => $input]);
    $crawler = $tester->render()->crawler();

    expect($this->reload($sample)->flagged)->toBeFalse()
        ->and($crawler)->toContainElement($cell . ' ' . Selectors::TOGGLE_COLUMN . ' ' . Selectors::CELL_ERROR, 'This value should be of type bool.')
        ->and($crawler)->toContainElement($cell . ' input[role="switch"][aria-invalid="true"]');

    $crawler = $tester->call('updateTableColumnState', ['column' => 'flagged', 'record' => (string) $sample->id, 'input' => true])->render()->crawler();

    expect($this->reload($sample)->flagged)->toBeTrue()
        ->and($crawler)->toContainElement($cell . ' ' . Selectors::editableCellState('1') . ' input[role="switch"][checked]:not([aria-invalid])')
        ->and($crawler->filter($cell . ' ' . Selectors::CELL_ERROR))->toHaveCount(0);
})->with([
    'an integer' => [1],
    'a numeric string' => ['1'],
]);

it("renders forms' switch on, off or disabled, named by its label and titled by its tooltip, with no tabindex", function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'lit') . ' ' . Selectors::TOGGLE_COLUMN . '[title="Light the shelf"]')
        ->and($crawler->filter(Selectors::cell('north', 'dim') . ' ' . Selectors::TOGGLE_COLUMN . '[title]'))->toHaveCount(0)
        ->and($crawler)->toContainElement(Selectors::cell('north', 'lit') . ' label' . FormsSelectors::TOGGLE . ' input[type="checkbox"][role="switch"][aria-label="Lit"][checked]:not([disabled])')
        ->and($crawler)->toContainElement(Selectors::cell('north', 'dim') . ' input[role="switch"][aria-label="Dim"]:not([checked])')
        ->and($crawler)->toContainElement(Selectors::cell('north', 'stuck') . ' input[role="switch"][disabled]')
        ->and($crawler->filter(Selectors::TOGGLE_COLUMN . ' [tabindex]'))->toHaveCount(0);
});

it('publishes its state on its root for the controller, and commits its switch on change', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'lit') . ' ' . Selectors::TOGGLE_COLUMN . Selectors::editableCellState('1') . Selectors::editableCell('lit', 'north') . ' input[role="switch"]' . Selectors::EDITABLE_CONTROL)
        ->and($crawler)->toContainElement(Selectors::cell('north', 'dim') . ' ' . Selectors::editableCellState('0'));
});

it('carries inline and its alignment on its root', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'lit') . ' ' . Selectors::TOGGLE_COLUMN . SupportSelectors::INLINE . SupportSelectors::align('center'))
        ->and($crawler)->toContainElement(Selectors::cell('north', 'dim') . ' ' . Selectors::TOGGLE_COLUMN . ':not(' . SupportSelectors::INLINE . ')' . SupportSelectors::NO_ALIGN);
});

describe('colours and icons', function (): void {
    it('paints its off colour on the switch and its on colour on the track', function (): void {
        $lit = Selectors::cell('north', 'lit');

        expect($this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler())
            ->toContainElement($lit . ' ' . FormsSelectors::TOGGLE . SupportSelectors::color('danger') . ':not(' . SupportSelectors::color('success') . ')')
            ->toContainElement($lit . ' ' . FormsSelectors::TOGGLE_TRACK . SupportSelectors::color('success') . ':not(' . SupportSelectors::color('danger') . ')');
    });

    it('falls back to a gray switch on a primary track while no colour is set', function (): void {
        $dim = Selectors::cell('north', 'dim');

        expect($this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler())
            ->toContainElement($dim . ' ' . FormsSelectors::TOGGLE . SupportSelectors::GRAY)
            ->toContainElement($dim . ' ' . FormsSelectors::TOGGLE_TRACK . SupportSelectors::COLORED . SupportSelectors::color('primary'));
    });

    it('paints an array palette as inline declarations on the element it colours', function (): void {
        $glow = Selectors::cell('north', 'glow');

        expect($this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler())
            ->toContainElement($glow . ' ' . FormsSelectors::TOGGLE . SupportSelectors::palette(Color::Zinc[500]) . ':not(' . SupportSelectors::palette(Color::Rose[500]) . ')')
            ->toContainElement($glow . ' ' . FormsSelectors::TOGGLE_TRACK . SupportSelectors::palette(Color::Rose[500]));
    });

    it('renders each icon in the span named for its state, and no span for an icon not given', function (): void {
        $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();
        $spans = static fn (string $column): array => $crawler->filter(Selectors::cell('north', $column) . ' ' . FormsSelectors::TOGGLE_ICON_OFF . ', ' . Selectors::cell('north', $column) . ' ' . FormsSelectors::TOGGLE_ICON_ON)
            ->each(static fn (Crawler $span): string => $span->attr('class') . ': ' . $span->filter('svg')->text());

        expect($spans('lit'))->toBe(['sy-fo-toggle-icon-off: Heart', 'sy-fo-toggle-icon-on: Star'])
            ->and($spans('dim'))->toBe([]);
    });
});

it('keeps its cell out of the record link a clickable row wraps its other cells in', function (): void {
    $crawler = $this->createLiveComponent(EditableCellLooksTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::cell('north', 'label') . ' a' . Selectors::CELL_WRAPPER . '[href="/larder/north"]')
        ->and($crawler)->toContainElement(Selectors::cell('north', 'lit') . ' div' . Selectors::CELL_WRAPPER)
        ->and($crawler->filter(Selectors::cell('north', 'lit') . ' a' . Selectors::CELL_WRAPPER))->toHaveCount(0);
});
