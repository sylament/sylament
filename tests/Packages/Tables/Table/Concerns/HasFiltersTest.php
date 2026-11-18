<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ArrangedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SessionFilteredLarderJarTable;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

describe('the trigger', function (): void {
    it('offers the filters of a table that has any behind a funnel named Filter in its toolbar, badged with how many are active while any is', function (): void {
        $unfiltered = $this->createLiveComponent(ProductTable::class)->render()->crawler();
        $tester = $this->createLiveComponent(FilterableLarderJarTable::class);
        $inactive = $tester->render()->crawler();

        $tester->filterTable('seal', JarSeal::Wax)->filterTable('labelled');

        expect($unfiltered->filter(Selectors::FILTERS_TRIGGER))->toHaveCount(0)
            ->and($unfiltered->filter(Selectors::TOOLBAR))->toHaveCount(0)
            ->and($inactive)->toContainElement(Selectors::TOOLBAR . ' button' . Selectors::FILTERS_TRIGGER . ' title', 'funnel-20-solid')
            ->and($inactive->filter(Selectors::FILTERS_TRIGGER_BADGE))->toHaveCount(0)
            ->and($tester->render()->crawler())->toContainElement(Selectors::FILTERS_TRIGGER_BADGE, '2');
    });

    it('keeps the modal command on the trigger and the styling hook on the remove-all button when a closure gives them attributes, and renders a relabelled apply and remove-all small', function (): void {
        $crawler = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => 'Modal', 'variants' => ['customized']])
            ->filterTable('labelled')
            ->render()
            ->crawler();
        $trigger = $crawler->filter(Selectors::FILTERS_TRIGGER . '[data-customized="trigger"]');

        expect($trigger->attr('command'))->toBe('show-modal')
            ->and($trigger->attr('commandfor'))->toBe($crawler->filter(Selectors::FILTERS_MODAL)->attr('id'))
            ->and($trigger->attr('data-action'))->toBeNull()
            ->and($crawler)->toContainElement(Selectors::FILTERS_MODAL . ' ' . SupportSelectors::BUTTON . SupportSelectors::size('sm') . '[data-live-action-param="applyTableFilters"]', 'Filter jars')
            ->and($crawler)->toContainElement(SupportSelectors::SMALL_ICON_BUTTON . '.sy-ta-filter-indicators-remove-all-btn[data-customized="remove-all"][aria-label="Clear jar filters"] title', 'x-mark-20-solid');
    });
});

describe('the layouts', function (): void {
    it('places the filters panel above or below the table, in more columns as the screen widens, with no trigger', function (string $layout, array $order): void {
        $crawler = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => $layout])->filterTable('labelled')->render()->crawler();
        $placement = $crawler->filter(Selectors::FILTER_INDICATOR . ', ' . Selectors::FILTERS . ', ' . Selectors::TABLE)->each(static fn (Crawler $node): string => match (true) {
            $node->matches(Selectors::TABLE) => 'table',
            $node->matches(Selectors::FILTERS) => 'filters',
            default => 'chip',
        });

        expect($placement)->toBe($order)
            ->and($crawler)->toContainElement(Selectors::FILTERS . ' ' . SchemasSelectors::schemaKeyed('tableFiltersForm') . SupportSelectors::columns('sm', 2) . SupportSelectors::columns('2xl', 5))
            ->and($crawler->filter(Selectors::FILTERS_TRIGGER))->toHaveCount(0);
    })->with([
        'above the content' => ['AboveContent', ['chip', 'filters', 'table']],
        'below the content' => ['BelowContent', ['chip', 'table', 'filters']],
    ]);

    it('lays the filters form out in one column in the dropdown, and in the count it is given whatever the layout', function (string $layout, array $variants, int $columns): void {
        $form = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => $layout, 'variants' => $variants])->render()->crawler()->filter(SchemasSelectors::schemaKeyed('tableFiltersForm'));

        expect($form)->toContainElement(SupportSelectors::columns('lg', $columns))
            ->and($form->filter(SupportSelectors::columns('sm', 2)))->toHaveCount(0);
    })->with([
        'the dropdown' => ['Dropdown', [], 1],
        'a count given above the content' => ['AboveContent', ['twoColumns'], 2],
    ]);

    it('sizes the dropdown panel by the width and the max height it is given, and the filters modal medium without a width', function (): void {
        $dropdown = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['sized']])->render()->crawler();
        $modal = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => 'Modal'])->render()->crawler();

        expect($dropdown)->toContainElement(SupportSelectors::DROPDOWN_PANEL . SupportSelectors::dropdownPanelStyle('width', '30rem') . SupportSelectors::dropdownPanelStyle('max-height', '20rem') . ' ' . Selectors::FILTERS)
            ->and($modal)->toContainElement(Selectors::FILTERS_MODAL . ' ' . SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('md'));
    });

    it('seeds the collapsible panel open only while a filter is active, under a summary whose funnel is no button and carries the count', function (): void {
        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => 'AboveContentCollapsible']);
        $inactive = $tester->render()->crawler();
        $active = $tester->filterTable('labelled')->render()->crawler();

        expect($inactive)->toContainElement('details:not([open]) ' . Selectors::FILTERS)
            ->and($active)->toContainElement('details[open] ' . Selectors::FILTERS)
            ->and($active)->toContainElement('summary span' . Selectors::FILTERS_TRIGGER . ' title', 'funnel-20-solid')
            ->and($active)->toContainElement('summary ' . Selectors::FILTERS_TRIGGER_BADGE, '1')
            ->and($active->filter('summary button'))->toHaveCount(0);
    });

    it('opens the filters in a dialog its trigger names by command, holding the bare form with apply and reset in its footer', function (): void {
        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => 'Modal']);
        $crawler = $tester->render()->crawler();
        $dialog = $crawler->filter(Selectors::FILTERS_MODAL);

        expect($dialog->attr('id'))->toBe($tester->component()->getTableHostId() . '.table.filters')
            ->and($crawler->filter(Selectors::FILTERS_TRIGGER)->attr('command'))->toBe('show-modal')
            ->and($crawler->filter(Selectors::FILTERS_TRIGGER)->attr('commandfor'))->toBe($dialog->attr('id'))
            ->and($dialog)->toContainElement(Selectors::filterField('labelled', 'isActive'))
            ->and($dialog)->toContainButtons(SupportSelectors::MODAL_FOOTER . ' button', ['Apply filters', 'Reset'])
            ->and($crawler->filter(Selectors::FILTERS))->toHaveCount(0);
    });

    it('renders no filters chrome on the hidden layout, yet filters the rows and chips each active filter', function (): void {
        $this->seed($plum = new LarderJar('Plum'), $unlabelled = new LarderJar());

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => 'Hidden'])
            ->filterTable('labelled')
            ->assertCanSeeTableRecords([$plum])
            ->assertCanNotSeeTableRecords([$unlabelled]);
        $crawler = $tester->render()->crawler();

        expect($crawler)->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Labelled')
            ->and($crawler->filter(Selectors::FILTERS_TRIGGER))->toHaveCount(0)
            ->and($crawler->filter(Selectors::FILTERS))->toHaveCount(0)
            ->and($crawler->filter(Selectors::FILTERS_MODAL))->toHaveCount(0);
    });

    it('refuses to render a layout whose chrome is not shipped, naming it and the layouts that are, while its form still sizes itself', function (): void {
        $host = new ArrangedJarFilterTable($this->entityManager(), new HookLog());
        $host->layout = 'BeforeContent';

        expect(fn () => $this->createLiveComponent(ArrangedJarFilterTable::class, ['layout' => 'BeforeContent'])->render())
            ->toThrow(RuntimeError::class, 'Table [' . ArrangedJarFilterTable::class . '] requests the [BeforeContent] filters layout, which needs chrome the toolkit does not ship yet; use FiltersLayout::Dropdown, Modal, AboveContent, AboveContentCollapsible, BelowContent or Hidden.')
            ->and($host->getTable()->getFiltersFormColumns())->toBe(1);
    });
});

describe('the panel', function (): void {
    it('lays out a group for each filter declared and each pushed after them, and for the last set declared alone', function (): void {
        $declared = $this->createLiveComponent(ArrangedJarFilterTable::class)->render()->crawler();
        $replaced = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['refiltered']])->render()->crawler();

        expect($declared)->toContainElement(Selectors::filterGroup('labelled'))
            ->and($declared)->toContainElement(Selectors::filterGroup('flags'))
            ->and($replaced)->toContainElement(Selectors::filterGroup('sealed'))
            ->and($replaced->filter(Selectors::filterGroup('labelled')))->toHaveCount(0)
            ->and($replaced->filter(Selectors::filterGroup('flags')))->toHaveCount(0);
    });

    it('heads the panel Filters and ends it with an Apply filters button only while the table defers its filters', function (array $variants, int $applyButtons): void {
        $crawler = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler)->toContainElement(Selectors::FILTERS . ' h3', 'Filters')
            ->and($crawler->filter(Selectors::FILTERS . ' button[data-live-action-param="applyTableFilters"]')->each(static fn (Crawler $button): string => $button->text()))->toBe(array_fill(0, $applyButtons, 'Apply filters'));
    })->with([
        'deferring' => [[], 1],
        'live' => [['live'], 0],
    ]);

    it('offers the reset as a link in the panel header, or as a button after Apply filters in its footer', function (array $variants, array $buttons, string $look): void {
        $crawler = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => $variants])->render()->crawler();

        expect($crawler)->toContainButtons(Selectors::FILTERS . ' [data-live-action-param]', $buttons)
            ->and($crawler)->toContainElement(Selectors::FILTERS . ' ' . $look . '[data-live-action-param="resetTableFiltersForm"]', 'Reset');
    })->with([
        'the header' => [[], ['Reset', 'Apply filters'], SupportSelectors::LINK],
        'the footer' => [['resetInFooter'], ['Apply filters', 'Reset'], SupportSelectors::BUTTON],
    ]);
});

describe('the indicators row', function (): void {
    it('shows an Active filters row while a filter is active, each chip removed by its own control and all of them by a remove-all button', function (): void {
        $tester = $this->createLiveComponent(FilterableLarderJarTable::class);
        $inactive = $tester->render()->crawler();
        $active = $tester->filterTable('seal', JarSeal::Wax)->render()->crawler();

        expect($inactive->filter(Selectors::FILTER_INDICATOR))->toHaveCount(0)
            ->and($active)->toContainElement('.sy-ta-filter-indicators-label', 'Active filters')
            ->and($active)->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Seal: Wax')
            ->and($active)->toContainElement(Selectors::removeFilterButton('seal') . '[data-live-action-param="removeTableFilter"]')
            ->and($active)->toContainElement(Selectors::REMOVE_ALL_FILTERS_BUTTON . '[data-live-action-param="removeTableFilters"] title', 'x-mark-20-solid');
    });

    it('offers no remove-all button while no chip can be removed', function (): void {
        $crawler = $this->createLiveComponent(ArrangedJarFilterTable::class)->filterTable('pinned')->render()->crawler();

        expect($crawler)->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Pinned')
            ->and($crawler->filter(Selectors::REMOVE_FILTER_BUTTON))->toHaveCount(0)
            ->and($crawler->filter(Selectors::REMOVE_ALL_FILTERS_BUTTON))->toHaveCount(0);
    });
});

describe('the session', function (): void {
    it('remembers the applied filters on the next page load only when the table persists them', function (string $host, array $labels): void {
        $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));
        $client = $this::getContainer()->get('test.client');

        $this->createLiveComponent($host, client: $client)->filterTable('seal', JarSeal::Wax);

        $page = $this->visit(TablePageController::url('Tables:' . new ReflectionClass($host)->getShortName()), client: $client);

        expect($page->filter(Selectors::ROW . ' ' . Selectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($labels);
    })->with([
        'persisted' => [SessionFilteredLarderJarTable::class, ['Plum']],
        'not persisted' => [FilterableLarderJarTable::class, ['Plum', 'Fig']],
    ]);
});

describe('the selection', function (): void {
    it('clears the selection on a filter change and keeps the selected records to the filtered ones, unless the table keeps its selection', function (array $variants, int $checked, string $stocked): void {
        $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

        $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => $variants])
            ->selectTableRecords([$plum, $fig])
            ->filterTable('seal', JarSeal::Wax);

        expect($tester->render()->crawler()->filter(Selectors::SELECTED_RECORD))->toHaveCount($checked);

        $tester->selectTableRecords([$plum, $fig])->callAction(TestAction::make('stock')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$stocked]);
    })->with([
        'deselecting' => [[], 0, 'stocked Plum'],
        'keeping the selection' => [['keepsSelection'], 1, 'stocked Plum, Fig'],
    ]);
});
