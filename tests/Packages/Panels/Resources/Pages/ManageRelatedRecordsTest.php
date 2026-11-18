<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderJars;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderJarShelf;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function (): void {
    $this->pantry = new Larder('Pantry');
    $this->pantry->addJar($this->apricot = new LarderJar('Apricot', seal: JarSeal::Wax, position: 1));
    $this->pantry->addJar($this->fig = new LarderJar('Fig', seal: JarSeal::Screw, position: 2));
    $this->pantry->addJar($this->quince = new LarderJar('Quince', seal: JarSeal::Wax, position: 3));
    $this->pantry->addBin(new LarderBin('B1'));

    $this->cellar = new Larder('Cellar');
    $this->cellar->addJar($this->plum = new LarderJar('Plum', position: 1));

    $this->seed($this->pantry, $this->cellar);

    $this->page = fn (string $class): Testable => $this->createLiveComponent($class, ['record' => $this->pantry->id, 'panelId' => 'related']);
    $this->labels = static fn (Crawler $page): array => $page->filter(TablesSelectors::columnCells('label'))->each(static fn (Crawler $cell): string => $cell->text());
});

it('lists the jars of the larder its URL names and of no other, inside the page chrome its root is classed for', function (): void {
    $page = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars");

    expect(($this->labels)($page))->toBe(['Apricot', 'Fig', 'Quince'])
        ->and($page->filter(Selectors::PAGE . ".sy-pa-resource-manage-related-records-page.sy-pa-resource-stocked-larders.sy-pa-resource-record-{$this->pantry->id}"))->toHaveCount(1)
        ->and($page)->toContainElement(Selectors::PAGE_HEADING)
        ->toContainElement(Selectors::HEADER_ACTIONS)
        ->toContainElement(Selectors::SUB_NAVIGATION);
});

it('heads the page "Manage <owner> <relationship title>", titles its document so with the owner\'s record label, and ends its breadcrumbs with the relationship title, unless the page names its own', function (string $page, string $heading, string $documentTitle, string $breadcrumb): void {
    $crawler = $this->visit("/related/stocked-larders/{$this->pantry->id}/{$page}");

    expect($crawler->filter(Selectors::PAGE_HEADING)->text())->toBe($heading)
        ->and($crawler->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith($documentTitle)
        ->and($crawler->filter(SupportSelectors::BREADCRUMB)->each(static fn (Crawler $crumb): string => $crumb->text()))->toBe(['Larders', 'Pantry', $breadcrumb]);
})->with([
    'neither' => ['jars', 'Manage Pantry Preserve Jars', 'Manage Larder Preserve Jars', 'Preserve Jars'],
    'a title' => ['shelf', 'Pantry shelf', 'Pantry shelf', 'Jar shelf'],
    'a breadcrumb' => ['bins', 'Manage Pantry Bins', 'Manage Larder Bins', 'Every bin'],
]);

it("lists itself in its record's band under the association's name with the stack icon, never the related resource's icon or its own relationship title, active on its own URL, unless it names its own label or icon", function (): void {
    $page = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars");
    $items = $page->filter(Selectors::SUB_NAVIGATION . ' a');

    expect($items->each(static fn (Crawler $item): string => $item->filter(Selectors::NAVIGATION_ITEM_LABEL)->text()))->toBe(['Jars', 'Jars', 'Bin store'])
        ->and($items->each(static fn (Crawler $item): string => $item->filter(SupportSelectors::ICON . ' title')->text()))->toBe(['rectangle-stack', 'check-circle', 'rectangle-stack'])
        ->and($page->filter(Selectors::ACTIVE_SUB_NAVIGATION_ITEM)->text())->toBe('rectangle-stack Jars')
        ->and($page->filter(Selectors::pageWithSubNavigation()))->toHaveCount(1);
});

it('shows its tab strip above the table on the first tab, and a page declaring no tabs none', function (): void {
    $jars = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars");
    $bins = $this->visit("/related/stocked-larders/{$this->pantry->id}/bins");

    expect($jars)->toContainButtons(SchemasSelectors::TAB, ['All', 'Waxed'])
        ->toContainElement(SchemasSelectors::TAB . '[aria-selected="true"]', 'All')
        ->and($bins->filter(SchemasSelectors::TAB))->toHaveCount(0)
        ->and($bins->filter(TablesSelectors::ROW))->toHaveCount(1);
});

it('opens sorted, searched, filtered and on the tab its URL names, and unsorted on a direction it does not know', function (string $query, array $labels, string $sortState): void {
    $page = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars?{$query}");

    expect(($this->labels)($page))->toBe($labels)
        ->and($page->filter(TablesSelectors::sortState('label', $sortState)))->toHaveCount(1);
})->with([
    'a tab and a sort' => ['tab=waxed&sort=label:desc', ['Quince', 'Apricot'], 'descending'],
    'a search' => ['search=ig', ['Fig'], 'none'],
    'a filter' => ['filters[seal][value]=screw', ['Fig'], 'none'],
    'a direction it does not know' => ['sort=label:sideways', ['Apricot', 'Fig', 'Quince'], 'none'],
]);

it('opens grouped by a group its table offers, ungrouped by one it does not, and carries no grouping for a blank one', function (string $grouping, int $groups, ?string $published): void {
    $page = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars?grouping={$grouping}");

    expect($page->filter(TablesSelectors::GROUP_HEADER_ROW))->toHaveCount($groups)
        ->and($this->renderedLiveProps($page)['tableGrouping'])->toBe($published)
        ->and(($this->labels)($page))->toHaveCount(3);
})->with([
    'an offered group' => ['seal', 2, 'seal'],
    'a group it does not offer' => ['position', 0, null],
    'a blank one' => ['', 0, null],
]);

describe('the owner is the page record', function (): void {
    it("creates a jar of the owner from its header in a modal on the page's form, labelled by the listed records", function (): void {
        $tester = ($this->page)(ManageLarderJarShelf::class)->mountAction('create');

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Create Larder Jar')
            ->toContainElement(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::INPUT);

        $tester->setActionData(['label' => 'Damson'])->callMountedAction()->assertActionNotMounted();

        $damson = $this->entityManager()->getRepository(LarderJar::class)->findOneBy(['label' => 'Damson']);

        expect($damson->larder->id)->toBe($this->pantry->id);

        $tester->assertCanSeeTableRecords([$damson]);
    });

    it("renames the owner from its header, the modal headed by the owner's title and filled from it", function (): void {
        $tester = ($this->page)(ManageLarderJars::class)->mountAction('rename');

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Edit Pantry');

        $tester->assertActionDataSet(['name' => 'Pantry'])
            ->setActionData(['name' => 'Still room'])
            ->callMountedAction()
            ->assertNoRedirect();

        expect($this->reload($this->pantry)->name)->toBe('Still room');
    });

    it("edits and views a row's jar in modals on the page's form, headed by the listed records' label and never the owner", function (): void {
        $tester = ($this->page)(ManageLarderJarShelf::class);

        $edit = $tester->mountAction(TestAction::make('edit')->table($this->fig))->assertActionDataSet(['label' => 'Fig'])->render()->crawler();

        expect($edit)->toContainElement(SupportSelectors::MODAL_HEADING, 'Edit larder jar');

        $view = $tester->unmountAction()->mountAction(TestAction::make('view')->table($this->fig))->render()->crawler()->filter(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::INPUT);

        expect($view->attr('value'))->toBe('Fig')
            ->and($view->attr('disabled'))->not->toBeNull();
    });

    it('deletes a row and stays on the page', function (): void {
        ($this->page)(ManageLarderJars::class)
            ->callAction(TestAction::make('delete')->table($this->fig))
            ->assertNoRedirect()
            ->assertCanSeeTableRecords([$this->apricot, $this->quince])
            ->assertCanNotSeeTableRecords([$this->fig]);

        expect($this->entityManager()->getRepository(LarderJar::class)->findBy(['label' => 'Fig']))->toBe([]);
    });
});
