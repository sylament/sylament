<?php

declare(strict_types=1);

use Sylament\Actions\EditAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditAllotment;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGarden;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditObservatory;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTerrace;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SproutsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\TaggedPlantsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\TelescopesRelationManager;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;
use Sylament\Tests\Fixtures\SecondaryEntity\Telescope;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function (): void {
    $this->garden = new Garden('Kitchen');
    $this->kale = new Seedling('Kale');
    $this->kale->garden = $this->garden;

    $this->leek = new Seedling('Leek');
    $this->leek->garden = $this->garden;

    $this->rhubarb = new Seedling('Rhubarb');
    $this->rhubarb->garden = new Garden('Orchard');

    $this->seed($this->garden, $this->kale, $this->leek, $this->rhubarb->garden, $this->rhubarb);

    $this->seedlings = fn (): object => $this->createLiveComponent(SeedlingsRelationManager::class, ['ownerRecord' => (string) $this->garden->id, 'pageClass' => EditGarden::class]);
});

describe('as a live component', function (): void {
    it('translates a title, badge and badge tooltip it returns as messages, in its table heading and its tab', function (): void {
        $tester = $this->createLiveComponent(SproutsRelationManager::class, ['ownerRecord' => (string) $this->garden->id, 'pageClass' => EditGarden::class]);
        $tab = SproutsRelationManager::getTabComponent($this->garden, EditGarden::class);

        expect($tester->render()->crawler())->toContainElement(TablesSelectors::HEADER . ' h2', 'Sprouts (translated)')
            ->and([$tab->getLabel(), $tab->getBadge(), $tab->getBadgeTooltip()])->toBe(['Sprouts (translated)', 'Sown (translated)', 'Sown this season (translated)']);
    });

    it("renders its own root with its owner's association alone, headed by its title, and sorts it", function (): void {
        $tester = ($this->seedlings)();

        expect($tester->render()->crawler()->filter(Selectors::relationManager(SeedlingsRelationManager::class)))->toHaveCount(1)
            ->and($tester->render()->crawler())->toContainElement(TablesSelectors::HEADER . ' h2', 'Seedlings');

        $tester->assertCanSeeTableRecords([$this->kale, $this->leek])
            ->assertCanNotSeeTableRecords([$this->rhubarb])
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$this->leek, $this->kale], inOrder: true);
    });

    it('keeps the owner as its key on the wire and resolves it once per request', function (): void {
        $tester = ($this->seedlings)()->sortTable('name');

        expect($tester->component()->ownerRecord)->toBe((string) $this->garden->id)
            ->and(array_filter($this->statementsRun(), static fn (string $statement): bool => str_contains($statement, ' FROM Garden ')))->toHaveCount(1);
    });

    it('applies its table() hook once to the table a request builds', function (): void {
        ($this->seedlings)()->sortTable('name');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['table']);
    });

    it('carries the owner of a composite key on the wire, listing and creating under that owner alone', function (string $pageClass, object $owner, object $neighbour, string $side): void {
        $mine = new Seedling('Kale');
        $mine->{$side} = $owner;

        $theirs = new Seedling('Rhubarb');
        $theirs->{$side} = $neighbour;
        $this->seed($owner, $neighbour, $mine, $theirs);
        $key = RecordKey::of($this->entityManager(), $owner);

        $tester = $this->createLiveComponent(SeedlingsRelationManager::class, ['ownerRecord' => $key, 'pageClass' => $pageClass])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->callAction(TestAction::make('create')->table(), data: ['name' => 'Chard']);

        $chard = $this->entityManager()->getRepository(Seedling::class)->findOneBy(['name' => 'Chard']);

        expect($tester->component()->ownerRecord)->toBe($key)
            ->and(RecordKey::of($this->entityManager(), $chard->{$side}))->toBe($key);
    })->with([
        'scalar fields' => fn (): array => [EditTerrace::class, new Terrace(7, 2), new Terrace(7, 3), 'terrace'],
        'an association plus a scalar' => function (): array {
            $garden = new Garden('Allotments');

            return [EditAllotment::class, new Allotment($garden, 2), new Allotment($garden, 3), 'allotment'];
        },
    ]);

    it('refuses to resolve an owner it cannot find, with a not-found error', function (string $key, string $pageClass, string $message): void {
        expect(fn () => $this->createLiveComponent(SeedlingsRelationManager::class, ['ownerRecord' => $key, 'pageClass' => $pageClass])->render())
            ->toThrow(NotFoundHttpException::class, $message);
    })->with([
        'a key no garden has' => ['999', EditGarden::class, 'No record matching [999] exists for the resource'],
        'no key' => ['', EditGarden::class, 'carries no owner record key to resolve'],
        'a page that is not a resource page' => ['1', Dashboard::class, 'was mounted against [' . Dashboard::class . '], which is not a resource page'],
    ]);

    it('refuses to render without the class of the page embedding it', function (): void {
        expect(fn () => $this->createLiveComponent(SeedlingsRelationManager::class, ['ownerRecord' => (string) $this->garden->id])->render())
            ->toThrow(LogicException::class, 'carries no page class');
    });
});

it('lists the related records of an owner another manager maps', function (): void {
    $observatory = new Observatory('Kitt Peak');
    $observatory->addTelescope($telescope = new Telescope('Hale'));
    $this->seed($observatory);

    expect($this->createLiveComponent(TelescopesRelationManager::class, ['ownerRecord' => (string) $observatory->id, 'pageClass' => EditObservatory::class])->render()->crawler())
        ->toContainElement(TablesSelectors::row((string) $telescope->id), 'Hale');
});

describe('its actions', function (): void {
    it('opens an edit modal over its own form inside its root, and saves through it', function (): void {
        $tester = ($this->seedlings)()->mountAction(TestAction::make('edit')->table($this->kale));

        expect($tester->render()->crawler())->toContainElement(Selectors::RELATION_MANAGER . ' ' . SupportSelectors::MODAL_HEADING, 'Edit Kale');

        $tester->assertSchemaStateSet(['name' => 'Kale'])
            ->fillForm(['name' => 'Kohlrabi'])
            ->callMountedAction()
            ->assertActionNotMounted();

        expect($this->reload($this->kale)->name)->toBe('Kohlrabi');
    });

    it('views a record in its own form with the field disabled', function (): void {
        $modal = ($this->seedlings)()->mountAction(TestAction::make('view')->table($this->kale))->render()->crawler()->filter(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::INPUT);

        expect($modal->attr('value'))->toBe('Kale')
            ->and($modal->attr('disabled'))->not->toBeNull();
    });

    it('deletes a row and stays where it is', function (): void {
        ($this->seedlings)()
            ->callAction(TestAction::make('delete')->table($this->kale))
            ->assertNoRedirect()
            ->assertCanSeeTableRecords([$this->leek])
            ->assertCanNotSeeTableRecords([$this->kale]);

        expect($this->entityManager()->getRepository(Seedling::class)->findBy(['name' => 'Kale']))->toBe([]);
    });

    it('joins what a create action outside its table makes to the owner, tests its form by default after it, and answers no relationship for another action', function (): void {
        $tester = ($this->seedlings)()->callAction('sow', data: ['name' => 'Chard']);

        expect($tester->assertHasNoFormErrors())->toBe($tester)
            ->and($this->entityManager()->getRepository(Seedling::class)->findOneBy(['name' => 'Chard'])->garden->id)->toBe($this->garden->id)
            ->and($tester->component()->getDefaultActionRelationship(EditAction::make()))->toBeNull();
    });
});

describe('its tabs and its title', function (): void {
    it('builds its tabs on the page from the properties it was mounted with and its owner, and opens on the first, scoped by it', function (): void {
        $garden = $this->entityManager()->find(Garden::class, $this->garden->id);
        $plants = array_map(static fn (string $name): Plant => new Plant($name), ['Basil', 'Bay', 'Mint']);
        array_walk($plants, $garden->addPlant(...));
        $this->seed($garden, ...$plants);

        $manager = $this->visit("/relations/gardens/{$garden->id}/edit?relation=plants")->filter(Selectors::relationManager(TaggedPlantsRelationManager::class));

        expect($manager)->toContainButtons(SchemasSelectors::TAB, ['Starting B', 'All of Kitchen'])
            ->toContainElement(SchemasSelectors::SELECTED_TAB, 'Starting B')
            ->and($manager->filter(TablesSelectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Basil', 'Bay']);
    });

    it('is titled by its title, else by its relationship name split into title-cased words', function (): void {
        $raisedBeds = new class extends RelationManager {
            protected static string $relationship = 'raisedBeds';
        };

        expect($this->createLiveComponent(TaggedPlantsRelationManager::class, ['ownerRecord' => (string) $this->garden->id, 'pageClass' => EditGarden::class])->render()->crawler())
            ->toContainElement(TablesSelectors::HEADER . ' h2', 'Planted')
            ->and($raisedBeds::getTitle($this->garden, EditGarden::class))->toBe('Raised Beds');
    });

    it("shows its title, icon and badge on the page's tab, the badge coloured and titled", function (): void {
        $page = $this->visit("/relations/gardens/{$this->garden->id}/edit");

        expect($page)->toContainElement(Selectors::relationManagerTab('seedlings'), 'Seedlingscheck-circle Sown')
            ->toContainElement(Selectors::relationManagerTab('seedlings') . ' ' . SupportSelectors::BADGE . SupportSelectors::color('success') . '[title="Sown this season"]', 'Sown');
    });
});
