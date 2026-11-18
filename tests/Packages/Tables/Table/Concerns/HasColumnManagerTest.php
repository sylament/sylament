<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ColumnManagerProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('the trigger', function (): void {
    it('offers the column manager behind a gray icon button named Column manager in the toolbar, opening its dropdown with no click of its own', function (): void {
        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class)->render()->crawler();
        $trigger = $crawler->filter(Selectors::TOOLBAR . ' ' . SupportSelectors::DROPDOWN_TRIGGER . ' ' . Selectors::COLUMN_MANAGER_TRIGGER . SupportSelectors::ICON_BUTTON . SupportSelectors::GRAY);

        expect($trigger)->toContainElement('title', 'view-columns-20-solid')
            ->and($trigger->attr('data-action'))->toBeNull()
            ->and($crawler)->toContainElement(SupportSelectors::DROPDOWN_PANEL . ' ' . Selectors::COLUMN_MANAGER);
    });

    it('shows the trigger and its toolbar exactly while a visible column can be toggled or the columns reordered, unless the table turns it off, which still hides the columns hidden by default', function (array $variants, int $triggers, array $headers): void {
        $this->seed(new Product('Lamp'));

        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler->filter(Selectors::COLUMN_MANAGER_TRIGGER))->toHaveCount($triggers)
            ->and($crawler->filter(Selectors::TOOLBAR))->toHaveCount($triggers)
            ->and($crawler->filter(Selectors::HEADER_CELLS)->each(static fn (Crawler $cell): string => $cell->text()))->toBe($headers);
    })->with([
        'toggleable columns' => [[], 1, ['ID', 'Name']],
        'reorderable columns' => [['plain', 'reorderable'], 1, ['ID', 'Name', 'Code']],
        'only a toggleable column the developer hid' => [['plain', 'hiddenToggleable'], 0, ['ID', 'Name', 'Code']],
        'the manager turned off over toggleable columns' => [['managerOff'], 0, ['ID', 'Name']],
    ]);

    it('rebuilds the trigger and the apply button through closures handed each action, keeping the modal command over the trigger attributes of its own', function (): void {
        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['modal', 'customized']]);
        $crawler = $tester->render()->crawler();
        $trigger = $crawler->filter('button[aria-label="Choose columns"][data-customized="trigger"]' . SupportSelectors::color('primary'));

        expect($trigger->attr('command'))->toBe('show-modal')
            ->and($trigger->attr('commandfor'))->toBe($tester->component()->getTableHostId() . '.table.column-manager')
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_TRIGGER))->toHaveCount(0)
            ->and($crawler)->toContainElement(Selectors::COLUMN_MANAGER_MODAL . ' ' . Selectors::COLUMN_MANAGER_APPLY, 'Show these columns');
    });
});

describe('the layouts', function (): void {
    it('opens the manager in a dialog titled Columns that its trigger names by command, holding the bare items with Apply and one Reset in its footer wherever the reset is placed', function (): void {
        $tester = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['modal', 'resetInFooter']]);
        $crawler = $tester->render()->crawler();
        $dialog = $crawler->filter(Selectors::COLUMN_MANAGER_MODAL);

        expect($dialog->attr('id'))->toBe($tester->component()->getTableHostId() . '.table.column-manager')
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_TRIGGER)->attr('command'))->toBe('show-modal')
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_TRIGGER)->attr('commandfor'))->toBe($dialog->attr('id'))
            ->and($dialog)->toContainElement(SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('md') . ' h2', 'Columns')
            ->and($dialog)->toContainElement(Selectors::COLUMN_MANAGER . ' ' . Selectors::COLUMN_MANAGER_ITEM_LABEL, 'Code')
            ->and($dialog)->toContainButtons(SupportSelectors::MODAL_FOOTER . ' button', ['Apply columns', 'Reset'])
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_RESET))->toHaveCount(1)
            ->and($crawler->filter('.sy-ta-col-manager, ' . SupportSelectors::DROPDOWN))->toHaveCount(0);
    });

    it('caps the dialog at the CSS length it is given', function (): void {
        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['modal', 'sized']])->render()->crawler();

        expect($crawler)->toContainElement(Selectors::COLUMN_MANAGER_MODAL . ' ' . SupportSelectors::MODAL_WINDOW . '[style*="max-width: 30rem"] h2', 'Columns');
    });

    it('sizes the dropdown by the width and the max height it is given and lays the items out column-wise in the count it is given, and in one column at the stylesheet width without', function (array $variants, string $panel, int $columns): void {
        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler)->toContainElement(SupportSelectors::DROPDOWN_PANEL . $panel . ' ' . Selectors::COLUMN_MANAGER . ' ' . SupportSelectors::GRID_DIRECTION_COLUMN . SupportSelectors::columnWiseColumns('lg', $columns) . SupportSelectors::columnWiseColumns('default', 1));
    })->with([
        'sized' => [['sized'], SupportSelectors::dropdownPanelStyle('width', '30rem') . SupportSelectors::dropdownPanelStyle('max-height', '20rem'), 2],
        'unsized' => [[], ':not(' . SupportSelectors::dropdownPanelStyle('width') . '):not(' . SupportSelectors::dropdownPanelStyle('max-height') . ')', 1],
    ]);
});

describe('the panel', function (): void {
    it('lists every column the developer did not hide, with a checkbox while any can be toggled and a drag handle while they are reorderable, alike in the dropdown and the modal', function (array $variants, int $checkboxes, int $handles): void {
        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler->filter(Selectors::COLUMN_MANAGER_ITEM_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['ID', 'Name', 'Code'])
            ->and($crawler->filter(Selectors::COLUMN_MANAGER_CHECKBOX))->toHaveCount($checkboxes)
            ->and($crawler->filter(SupportSelectors::SORTABLE_HANDLE))->toHaveCount($handles);
    })->with([
        'toggleable columns' => [[], 3, 0],
        'toggleable columns beside one the developer hid' => [['hiddenToggleable'], 3, 0],
        'reorderable columns in the dropdown' => [['plain', 'reorderable'], 0, 3],
        'reorderable columns in the modal' => [['plain', 'reorderable', 'modal'], 0, 3],
    ]);

    it('ends the panel with an Apply columns button while deferred, its items applying each change at once without one', function (array $variants, array $apply, string $live): void {
        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler->filter(Selectors::COLUMN_MANAGER_APPLY)->each(static fn (Crawler $button): string => $button->text()))->toBe($apply)
            ->and($crawler->filter(Selectors::COLUMN_MANAGER)->attr('data-sylament--tables--column-manager-live-value'))->toBe($live);
    })->with([
        'deferred' => [[], ['Apply columns'], 'false'],
        'live' => [['live'], [], 'true'],
    ]);

    it('moves the Reset into the footer beside Apply when the table places it there', function (): void {
        $crawler = $this->createLiveComponent(ColumnManagerProductTable::class, ['variants' => ['resetInFooter']])->render()->crawler();

        expect($crawler)->toContainButtons(Selectors::COLUMN_MANAGER_PANEL_FOOTER . ' button', ['Apply columns', 'Reset']);
    });
});
