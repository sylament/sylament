<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGarden;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTwinBedGarden;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\TaggedPlantsRelationManager;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

beforeEach(function (): void {
    $this->garden = new Garden('Kitchen');
    $kale = new Seedling('Kale');
    $kale->garden = $this->garden;

    $plants = array_map(static fn (string $name): Plant => new Plant($name), ['Basil', 'Bay', 'Mint']);
    array_walk($plants, $this->garden->addPlant(...));

    $this->seed($this->garden, $kale, ...$plants);
});

it('shows a tab per registration the record may see, in order, leaving out a hidden manager and a group of hidden ones', function (): void {
    $page = $this->visit("/relations/gardens/{$this->garden->id}/edit");

    expect($page)->toContainButtons(Selectors::RELATION_MANAGER_TAB, ['Seedlingscheck-circle Sown', 'Planted', 'rectangle-stackBeds 1'])
        ->and($page->filter(Selectors::RELATION_MANAGER))->toHaveCount(1)
        ->and($page)->toContainElement(Selectors::relationManager(SeedlingsRelationManager::class) . ' ' . TablesSelectors::columnCells('name'), 'Kale');
});

it('opens the tab ?relation= names, and the first when it names none the record shows', function (string $query, string $active, string $manager, array $rows): void {
    $page = $this->visit("/relations/gardens/{$this->garden->id}/edit{$query}");

    expect($page->filter(Selectors::RELATION_MANAGER_TAB . '[aria-selected="true"]')->attr('data-tab-key'))->toBe($active)
        ->and($page->filter(Selectors::relationManager($manager) . ' ' . TablesSelectors::columnCells('name'))->each(static fn (Crawler $row): string => $row->text()))->toBe($rows)
        ->and(json_decode((string) $page->filter(Selectors::PAGE)->attr('data-live-query-mapping-value'), true))->toBe(['activeRelationManager' => ['name' => 'relation']]);
})->with([
    'a key registered after a hidden one' => ['?relation=plants', 'plants', TaggedPlantsRelationManager::class, ['Basil', 'Bay']],
    "the hidden manager's key" => ['?relation=rows', 'seedlings', SeedlingsRelationManager::class, ['Kale']],
    'no key' => ['', 'seedlings', SeedlingsRelationManager::class, ['Kale']],
]);

it("mounts a manager with its own default properties, and a configuration's properties over them", function (): void {
    $group = $this->visit("/relations/gardens/{$this->garden->id}/edit?relation=beds");
    $configured = $this->visit("/relations/gardens/{$this->garden->id}/edit?relation=plants");

    expect($group)->toContainElement(Selectors::relationManager(TaggedPlantsRelationManager::class) . ' ' . SchemasSelectors::SELECTED_TAB, 'Starting M')
        ->toContainElement(Selectors::relationManager(TaggedPlantsRelationManager::class) . ' ' . TablesSelectors::columnCells('name'), 'Mint')
        ->and($configured)->toContainElement(Selectors::relationManager(TaggedPlantsRelationManager::class) . ' ' . SchemasSelectors::SELECTED_TAB, 'Starting B');
});

it('renders the managers after the form, never inside it', function (): void {
    $page = $this->visit("/relations/gardens/{$this->garden->id}/edit");

    expect($page->filter(Selectors::FORM))->toHaveCount(1)
        ->and($page->filter(Selectors::RELATION_MANAGER))->toHaveCount(1)
        ->and($page->filter(Selectors::FORM . ' ' . Selectors::RELATION_MANAGER))->toHaveCount(0);
});

it('mounts a lone registration bare, with no tab bar, and activates its list index as a string', function (): void {
    $terrace = new Terrace(7, 2);
    $leek = new Seedling('Leek');
    $leek->terrace = $terrace;
    $this->seed($terrace, $leek);

    $page = $this->visit('/relations/terraces/' . RecordKey::of($this->entityManager(), $terrace) . '/edit');

    expect($page)->toContainElement(Selectors::relationManager(SeedlingsRelationManager::class) . ' ' . TablesSelectors::columnCells('name'), 'Leek')
        ->and($page->filter(Selectors::RELATION_MANAGER_TAB))->toHaveCount(0)
        ->and($this->renderedLiveProps($page)['activeRelationManager'])->toBe('0');
});

it("mounts a lone group's managers together with no tab bar, one identity per entry", function (): void {
    $allotment = new Allotment($this->entityManager()->find(Garden::class, $this->garden->id), 3);
    $leek = new Seedling('Leek');
    $leek->allotment = $allotment;
    $this->seed($allotment, $leek);

    $page = $this->visit('/relations/allotments/' . RecordKey::of($this->entityManager(), $allotment) . '/edit');
    $managers = $page->filter(Selectors::relationManager(SeedlingsRelationManager::class));

    expect($managers->each(static fn (Crawler $manager): string => $manager->filter(TablesSelectors::columnCells('name'))->text()))->toBe(['Leek', 'Leek'])
        ->and(array_unique($managers->each(static fn (Crawler $manager): ?string => $manager->attr('id'))))->toHaveCount(2)
        ->and($page->filter(Selectors::RELATION_MANAGER_TAB))->toHaveCount(0);
});

it('renders a manager two groups hold under one key with one live id across a switch between their tabs', function (): void {
    $tester = $this->createLiveComponent(EditTwinBedGarden::class, ['record' => $this->garden->id, 'panelId' => 'relations']);
    $east = $tester->render()->crawler();
    $west = $tester->set('activeRelationManager', 'west')->render()->crawler();

    expect($east->filter(Selectors::RELATION_MANAGER_TAB . '[aria-selected="true"]')->attr('data-tab-key'))->toBe('east')
        ->and($west->filter(Selectors::RELATION_MANAGER_TAB . '[aria-selected="true"]')->attr('data-tab-key'))->toBe('west')
        ->and($east->filter(Selectors::relationManager(SeedlingsRelationManager::class))->attr('id'))->toStartWith('sy-live-')
        ->and($west->filter(Selectors::relationManager(SeedlingsRelationManager::class))->attr('id'))->toBe($east->filter(Selectors::relationManager(SeedlingsRelationManager::class))->attr('id'));
});

it('renders no manager and no tab bar for a resource that registers none', function (): void {
    $this->seed($article = new Article('Draft'));

    $page = $this->visit("/admin/articles/{$article->id}/edit");

    expect($page->filter(Selectors::FORM))->toHaveCount(1)
        ->and($page->filter(Selectors::RELATION_MANAGER))->toHaveCount(0)
        ->and($page->filter(SchemasSelectors::TAB))->toHaveCount(0);
});

it('switches the tab on a live request, and heals a key the registrations do not name back to the first', function (): void {
    $tester = $this->createLiveComponent(EditGarden::class, ['record' => $this->garden->id, 'panelId' => 'relations'])
        ->set('activeRelationManager', 'beds');

    expect($tester->render()->crawler()->filter(Selectors::RELATION_MANAGER)->each(static fn (Crawler $manager): ?string => $manager->attr('data-live-name-value')))
        ->toBe([str_replace('\\', ':', SeedlingsRelationManager::class), str_replace('\\', ':', TaggedPlantsRelationManager::class)])
        ->and($this->renderedLiveProps($tester->set('activeRelationManager', 'gone')->render()->crawler())['activeRelationManager'])->toBe('seedlings');
});

it('reads every tab badge afresh on each live request of the page', function (): void {
    $tester = $this->createLiveComponent(EditGarden::class, ['record' => $this->garden->id, 'panelId' => 'relations']);

    $leek = new Seedling('Leek');
    $leek->garden = $this->entityManager()->find(Garden::class, $this->garden->id);
    $this->seed($leek);

    expect($tester->set('activeRelationManager', 'plants')->render()->crawler())->toContainElement(Selectors::relationManagerTab('beds'), 'rectangle-stackBeds 2');
});
