<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Tables\Table;
use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderBins;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderJars;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderJarShelf;
use Sylament\Tests\Fixtures\Panels\Resources\PreserveJarResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function (): void {
    $this->pantry = new Larder('Pantry');
    $this->pantry->addJar($this->apricot = new LarderJar(ManageLarderJarShelf::UNVIEWABLE_LABEL, seal: JarSeal::Wax, position: 1));
    $this->pantry->addJar($this->fig = new LarderJar('Fig', seal: JarSeal::Screw, position: 2));
    $this->pantry->addJar($this->quince = new LarderJar(PreserveJarResource::UNEDITABLE_LABEL, seal: JarSeal::Wax, position: 3));
    $this->pantry->addBin($this->bin = new LarderBin('B1'));

    $this->cellar = new Larder('Cellar');
    $this->cellar->addJar($this->plum = new LarderJar('Plum', position: 1));

    $this->seed($this->pantry, $this->cellar);

    $this->page = fn (string $class, ?Larder $owner = null): Testable => $this->createLiveComponent($class, ['record' => ($owner ?? $this->pantry)->id, 'panelId' => 'related']);
    $this->labels = static fn (Crawler $page): array => $page->filter(TablesSelectors::columnCells('label'))->each(static fn (Crawler $cell): string => $cell->text());
});

it('refuses a page naming no relationship even with a related resource, naming the page and the property', function (): void {
    $unnamed = new class extends ManageRelatedRecords {
        protected static ?string $relatedResource = PreserveJarResource::class;
    };

    expect(fn (): string => $unnamed::getRelationshipName())->toThrow(LogicException::class, 'The relationship table [' . $unnamed::class . '] names no relationship — set the $relationship static property');
});

it("titles its relationship by the title it names, else by its related resource's plural label, else by the relationship's name", function (string $page, string $title): void {
    expect(($this->page)($page)->component()->getRelationshipTitle())->toBe($title);
})->with([
    'a title' => [ManageLarderJarShelf::class, 'Jar shelf'],
    'a related resource' => [ManageLarderJars::class, 'Preserve Jars'],
    'neither' => [ManageLarderBins::class, 'Bins'],
]);

it("lists the owner's association, searched and sorted inside it, for whichever owner the page is mounted on, and on a page leaving its table to the trait", function (): void {
    ($this->page)(ManageLarderJars::class)
        ->assertCanSeeTableRecords([$this->apricot, $this->fig, $this->quince])
        ->assertCanNotSeeTableRecords([$this->plum])
        ->searchTable('p')
        ->assertCanSeeTableRecords([$this->apricot])
        ->assertCountTableRecords(1)
        ->searchTable('')
        ->sortTable('label', 'desc')
        ->assertCanSeeTableRecords([$this->quince, $this->fig, $this->apricot], inOrder: true);

    ($this->page)(ManageLarderJars::class, $this->cellar)
        ->assertCanSeeTableRecords([$this->plum])
        ->assertCountTableRecords(1);

    ($this->page)(ManageLarderBins::class)->assertCanSeeTableRecords([$this->bin]);
});

it('scopes the listing by the active tab', function (): void {
    ($this->page)(ManageLarderJars::class)
        ->set('activeTab', 'waxed')
        ->assertCanSeeTableRecords([$this->apricot, $this->quince])
        ->assertCanNotSeeTableRecords([$this->fig]);
});

it("opens a row's view headed by that row's jar on the related resource's infolist over its form, and a page with neither a related resource nor a form on an empty schema", function (): void {
    $view = ($this->page)(ManageLarderJars::class)->mountAction(TestAction::make('view')->table($this->fig))->render()->crawler();

    expect($view)->toContainElement(SupportSelectors::MODAL_HEADING, 'View Fig')
        ->and($view->filter(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::INPUT)->attr('value'))->toBe('Fig')
        ->and($view->filter(SupportSelectors::OPEN_MODAL)->text())->toContain(PreserveJarResource::VIEW_NOTE);

    $create = ($this->page)(ManageLarderBins::class)->mountAction('create')->assertActionMounted('create')->render()->crawler();

    expect($create)->toContainElement(SupportSelectors::MODAL_HEADING, 'Create Larder Bin')
        ->and($create->filter(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::INPUT))->toHaveCount(0);
});

it('labels the table by the related resource and lays the sortable, searchable column it declares on it', function (): void {
    $jars = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars");
    $empty = $this->visit("/related/stocked-larders/{$this->cellar->id}/jars?tab=waxed");

    expect($jars->filter(TablesSelectors::sortTrigger('label')))->toHaveCount(1)
        ->and($jars->filter(TablesSelectors::SEARCH))->toHaveCount(1)
        ->and($jars->filter(Selectors::HEADER_ACTIONS . ' a')->text())->toBe('New jar')
        ->and($empty->filter(TablesSelectors::EMPTY_STATE)->text())->toContain('No preserve jars');
});

it("links each row to its related record's edit page, and with no page to open mounts the row's view, else its edit, for a row it hides the one on", function (): void {
    $jars = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars");
    $shelf = $this->visit("/related/stocked-larders/{$this->pantry->id}/shelf");
    $cell = static fn (Crawler $page, LarderJar $jar): Crawler => $page->filter(TablesSelectors::cell((string) $jar->id, 'label') . ' ' . TablesSelectors::CELL_WRAPPER);

    expect($cell($jars, $this->apricot)->attr('href'))->toBe("http://localhost/related/stocked-larders/{$this->pantry->id}/preserve-jars/{$this->apricot->id}/edit")
        ->and($cell($jars, $this->fig)->attr('href'))->toBe("http://localhost/related/stocked-larders/{$this->pantry->id}/preserve-jars/{$this->fig->id}/edit")
        ->and($cell($jars, $this->quince)->nodeName())->toBe('button')
        ->and($cell($jars, $this->quince)->attr('data-live-name-param'))->toBe('view')
        ->and($cell($shelf, $this->fig)->attr('data-live-name-param'))->toBe('view')
        ->and($cell($shelf, $this->apricot)->attr('data-live-name-param'))->toBe('edit');
});

it("links the related create and edit actions to the related resource's pages, carrying the owner only where that resource nests under it, and leaves an action on another record or with no page a modal", function (): void {
    $jars = $this->visit("/related/stocked-larders/{$this->pantry->id}/jars");
    $flat = $this->visit("/related/stocked-larders/{$this->pantry->id}/flat-jars");

    expect($jars->filter(Selectors::HEADER_ACTIONS . ' a')->each(static fn (Crawler $link): ?string => $link->attr('href')))->toBe(["http://localhost/related/stocked-larders/{$this->pantry->id}/preserve-jars/create"])
        ->and($jars->filter(Selectors::HEADER_ACTIONS . ' button[data-live-name-param="rename"]'))->toHaveCount(1)
        ->and($jars->filter(TablesSelectors::row((string) $this->fig->id) . ' ' . TablesSelectors::RECORD_ACTIONS . ' a')->each(static fn (Crawler $link): ?string => $link->attr('href')))->toBe(["http://localhost/related/stocked-larders/{$this->pantry->id}/preserve-jars/{$this->fig->id}/edit"])
        ->and($flat->filter(TablesSelectors::row((string) $this->fig->id) . ' ' . TablesSelectors::RECORD_ACTIONS . ' a')->attr('href'))->toBe("http://localhost/related/flat-jars/{$this->fig->id}/edit")
        ->and($flat->filter(Selectors::HEADER_ACTIONS . ' a'))->toHaveCount(0)
        ->and($flat)->toContainElement(Selectors::HEADER_ACTIONS . ' button[data-live-name-param="create"]', 'New larder jar');
});

it("keeps a record URL configured for every table over the related record's page", function (): void {
    Table::configureUsing(static fn (Table $table): Table => $table->recordUrl(static fn (LarderJar $record): string => "/labels/{$record->id}"));

    expect($this->visit("/related/stocked-larders/{$this->pantry->id}/jars")->filter(TablesSelectors::cell((string) $this->fig->id, 'label') . ' ' . TablesSelectors::CELL_WRAPPER)->attr('href'))->toBe("/labels/{$this->fig->id}");
});

it("pages its listing under a parameter the page's class names, whatever identifier its table names", function (): void {
    expect(($this->labels)($this->visit("/related/stocked-larders/{$this->pantry->id}/shelf?manageLarderJarShelfPage=2")))->toBe([PreserveJarResource::UNEDITABLE_LABEL])
        ->and(($this->labels)($this->visit("/related/stocked-larders/{$this->pantry->id}/shelf?shelfPage=2")))->toBe(['Apricot', 'Fig']);
});
