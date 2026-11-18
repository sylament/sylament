<?php

declare(strict_types=1);

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Routing\PanelRouteLoader;
use Sylament\Tests\Fixtures\Entity\Dock;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Voyage;
use Sylament\Tests\Fixtures\Panels\Resources\AmbiguousVoyageResource;
use Sylament\Tests\Fixtures\Panels\Resources\ArrivingVoyageResource;
use Sylament\Tests\Fixtures\Panels\Resources\DepartingVoyageResource;
use Sylament\Tests\Fixtures\Panels\Resources\DockedSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\DockResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenPlantResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\GreenhouseVentPlantResource;
use Sylament\Tests\Fixtures\Panels\Resources\GreenhouseVentSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\LarderBinNoteResource;
use Sylament\Tests\Fixtures\Panels\Resources\LarderBinResource;
use Sylament\Tests\Fixtures\Panels\Resources\RecordParameterSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\RepeatedParameterNoteResource;
use Sylament\Tests\Fixtures\Panels\Resources\RootlessSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\StockedCrockResource;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RouteCollection;

/*
 * A chain the loader refuses is built inline over a registry of its own: a panel the loader
 * refuses can never join the kernel's, whose every route build it would break.
 */

beforeEach(function (): void {
    $this->resource = fn (string $class): Resource => $this::getContainer()->get(ResourceRegistry::class)->get($class);
    $this->routes = fn (PanelProvider $provider): RouteCollection => new PanelRouteLoader(new PanelRegistry([$provider]), $this::getContainer()->get(ResourceRegistry::class))();
});

describe('the registration', function (): void {
    it('answers the parent it declares as a registration over itself, built once, and none for a resource declaring no parent', function (): void {
        $seedlings = ($this->resource)(GardenSeedlingResource::class);
        $registration = $seedlings->getParentResourceRegistration();

        expect($seedlings->getParentResource())->toBe(GardenResource::class)
            ->and($registration?->parentResource)->toBe(GardenResource::class)
            ->and($registration?->childResource)->toBe(GardenSeedlingResource::class)
            ->and($registration?->getRelationshipName())->toBeNull()
            ->and($seedlings->getParentResourceRegistration())->toBe($registration)
            ->and(($this->resource)(GardenResource::class)->getParentResource())->toBeNull()
            ->and(($this->resource)(GardenResource::class)->getParentResourceRegistration())->toBeNull();
    });

    it('nests under the parent an overridden getParentResource() answers, as under the static', function (): void {
        $plants = ($this->resource)(GardenPlantResource::class);

        expect($plants->getParentResourceRegistration()?->parentResource)->toBe(GardenResource::class)
            ->and($this::getContainer()->get('router')->getRouteCollection()->get('sylament.nested.resources.gardens.plants.edit')?->getPath())
            ->toBe('/nested/gardens/{gardens}/garden-plants/{record}/edit');
    });

    it('builds itself as the parent of the child it names, which an override of the child names the association on, either way it is written', function (): void {
        $registration = ($this->resource)(DockResource::class)->asParent(ArrivingVoyageResource::class);

        expect($registration->parentResource)->toBe(DockResource::class)
            ->and($registration->childResource)->toBe(ArrivingVoyageResource::class)
            ->and($registration->getRelationshipName())->toBeNull()
            ->and(($this->resource)(ArrivingVoyageResource::class)->getParentRelationshipName())->toBe('arrivals')
            ->and(($this->resource)(DepartingVoyageResource::class)->getParentRelationshipName())->toBe('departures')
            ->and(($this->resource)(DepartingVoyageResource::class)->getParentResourceRegistration()?->childResource)->toBe(DepartingVoyageResource::class);
    });
});

describe('the parent relationship', function (): void {
    it("reads the one to-many association of the parent's record targeting its own off the mapping, never inflecting the record's name", function (): void {
        expect(($this->resource)(LarderBinResource::class)->getParentRelationshipName())->toBe('bins')
            ->and(($this->resource)(LarderBinNoteResource::class)->getParentRelationshipName())->toBe('notes');
    });

    it('refuses to derive the relationship when no association or several target its record, naming both records and the fix', function (string $resource, string $message): void {
        expect(fn (): string => ($this->resource)($resource)->getParentRelationshipName())->toThrow(LogicException::class, $message);
    })->with([
        'none' => [DockedSeedlingResource::class, sprintf('Resource [%s] cannot derive its parent relationship from [%s]: 0 association(s) target [%s]. Override getParentResourceRegistration() and call ->relationship() naming the association.', DockedSeedlingResource::class, Dock::class, Seedling::class)],
        'several' => [AmbiguousVoyageResource::class, sprintf('Resource [%s] cannot derive its parent relationship from [%s]: 2 association(s) target [%s]. Override getParentResourceRegistration() and call ->relationship() naming the association.', AmbiguousVoyageResource::class, Dock::class, Voyage::class)],
    ]);
});

describe('the route parameter', function (): void {
    it("carries the parent under the field completing the association, never singularised, else the parent record's snake-cased name", function (string $resource, string $parameter): void {
        expect(($this->resource)($resource)->getParentRouteParameterName())->toBe($parameter);
    })->with([
        "a one-to-many's mapped-by field" => [GardenSeedlingResource::class, 'garden'],
        "a many-to-many's inversed-by field" => [GardenPlantResource::class, 'gardens'],
        'a many-to-many no field completes' => [GreenhouseVentPlantResource::class, 'greenhouse_vent'],
    ]);
});

describe('the parent scope', function (): void {
    it("narrows its own record query to one parent's records under its own root alias, keeping every row its query hides hidden", function (string $resource, Closure $records): void {
        $rose = new Garden('Rose');
        $oak = new Garden('Oak');
        $this->seed($rose, $oak, ...$records($rose, $oak));

        $scoped = ($this->resource)($resource)->getParentScopedRecordQuery($this->entityManager()->find(Garden::class, $rose->id));
        $names = array_map(static fn (Plant|Seedling $record): string => $record->name, $scoped->getQuery()->getResult());

        expect($names)->toEqualCanonicalizing(['Fern', 'Sage']);
    })->with([
        'a one-to-many, rooted at its own alias' => [GardenSeedlingResource::class, static function (Garden $rose, Garden $oak): array {
            $records = [];

            foreach ([[$rose, 'Fern'], [$rose, 'Sage'], [$rose, GardenSeedlingResource::HIDDEN_PREFIX . ' moss'], [$oak, 'Ivy']] as [$garden, $name]) {
                $records[] = $seedling = new Seedling($name);
                $seedling->garden = $garden;
            }

            return $records;
        }],
        'a many-to-many' => [GardenPlantResource::class, static function (Garden $rose, Garden $oak): array {
            $rose->addPlant($fern = new Plant('Fern'));
            $rose->addPlant($sage = new Plant('Sage'));

            $oak->addPlant($ivy = new Plant('Ivy'));

            return [$fern, $sage, $ivy];
        }],
    ]);

    it('refuses a record query naming no root alias, by the resource', function (): void {
        $this->seed($rose = new Garden('Rose'));

        expect(fn (): mixed => ($this->resource)(RootlessSeedlingResource::class)->getParentScopedRecordQuery($rose))
            ->toThrow(LogicException::class, sprintf('The record query of resource [%s] names no root alias to scope to its parent record.', RootlessSeedlingResource::class));
    });
});

describe('the attach', function (): void {
    it("writes the parent onto the association's owning side in memory, writing no row until the caller flushes", function (string $resource, Closure $record, Closure $holds): void {
        $this->seed($rose = new Garden('Rose'));
        $rose = $this->entityManager()->find(Garden::class, $rose->id);
        $child = $record();

        ($this->resource)($resource)->attachRecordToParent($child, $rose);

        expect(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))->toBe([])
            ->and($holds($child, $rose))->toBeTrue();

        $this->entityManager()->persist($child);
        $this->entityManager()->flush();
        $this->entityManager()->clear();

        expect($holds($this->reload($child), $this->reload($rose)))->toBeTrue();
    })->with([
        'a one-to-many the child owns' => [GardenSeedlingResource::class, static fn (): Seedling => new Seedling('Fern'), static fn (Seedling $seedling, Garden $garden): bool => $seedling->garden?->id === $garden->id],
        'a many-to-many the parent owns' => [GardenPlantResource::class, static fn (): Plant => new Plant('Fern'), static fn (Plant $plant, Garden $garden): bool => $garden->plants->exists(static fn (int $key, Plant $held): bool => $held->name === $plant->name)],
    ]);
});

describe('routes', function (): void {
    it("mounts its pages under each ancestor's slug and parameter, root-first, named by the parent's base name and the relationship, with its own slug kept", function (): void {
        $routes = $this::getContainer()->get('router')->getRouteCollection();
        $seedling = $routes->get('sylament.nested.resources.gardens.seedlings.edit');
        $note = $routes->get('sylament.nested.resources.stocked-larders.bins.notes.edit');

        expect(($this->resource)(GardenSeedlingResource::class)->getRouteBaseName($this::getContainer()->get(PanelRegistry::class)->get('nested')))->toBe('sylament.nested.resources.gardens.seedlings')
            ->and($seedling?->getPath())->toBe('/nested/gardens/{garden}/garden-seedlings/{record}/edit')
            ->and($seedling?->getDefault('_sylament_parent_params'))->toBe(['garden'])
            ->and($seedling?->getRequirements())->toBe([])
            ->and($note?->getPath())->toBe('/nested/stocked-larders/{larder}/larder-bins/{bin}/larder-bin-notes/{record}/edit')
            ->and($note?->getDefault('_sylament_parent_params'))->toBe(['larder', 'bin'])
            ->and($routes->get('sylament.nested.resources.gardens.edit')?->getDefaults())->not->toHaveKey('_sylament_parent_params');
    });

    it('refuses at route build a chain whose parent its panel does not register, naming both resources and the panel', function (): void {
        $orphan = new class extends PanelProvider {
            public function panel(Panel $panel): Panel
            {
                return $panel->id('nested-orphan')->resources([GardenSeedlingResource::class]);
            }
        };

        expect(fn (): RouteCollection => ($this->routes)($orphan))
            ->toThrow(LogicException::class, sprintf('Resource "%s" nests under "%s", which panel "nested-orphan" does not register — register the parent on the panel.', GardenSeedlingResource::class, GardenResource::class));
    });

    it("refuses at route build a parent parameter colliding with the record parameter or with an ancestor's", function (array $resources, string $message): void {
        $colliding = new class ($resources) extends PanelProvider {
            /**
             * @param list<class-string<Resource>> $resources
             */
            public function __construct(private readonly array $resources)
            {
            }

            public function panel(Panel $panel): Panel
            {
                return $panel->id('nested-colliding')->resources($this->resources);
            }
        };

        expect(fn (): RouteCollection => ($this->routes)($colliding))->toThrow(LogicException::class, $message);
    })->with([
        'the record parameter' => [[GardenResource::class, RecordParameterSeedlingResource::class], sprintf('Nested resource "%s" derives the route parameter "{record}", which collides with the record parameter.', RecordParameterSeedlingResource::class)],
        "an ancestor's" => [[StockedLarderResource::class, LarderBinResource::class, RepeatedParameterNoteResource::class], sprintf('Nested resource "%s" derives the route parameter "{larder}", which collides with an ancestor parameter.', RepeatedParameterNoteResource::class)],
    ]);
});

describe('urls', function (): void {
    beforeEach(function (): void {
        $this->pantry = new Larder('Pantry');
        $this->pantry->addBin($this->bin = new LarderBin('B1'));
        $this->pantry->addBin($this->otherBin = new LarderBin('C1'));

        $this->bin->addNote($this->note = new LarderBinNote('Use first'));
        $this->seed($this->pantry);

        $this->noteUrl = "/nested/stocked-larders/{$this->pantry->id}/larder-bins/B1/larder-bin-notes/{$this->note->id}/edit";
        $this->notes = ($this->resource)(LarderBinNoteResource::class);
    });

    it('mints a nested URL from ancestor records or keys, each record keyed by the resource that owns it, and refuses one missing an ancestor', function (): void {
        $rose = new Garden('Rose');
        $rose->seedlings->add($fern = new Seedling('Fern'));

        $fern->garden = $rose;
        $this->seed($rose, $fern);

        $seedlings = ($this->resource)(GardenSeedlingResource::class);
        $seedlingUrl = "/nested/gardens/{$rose->id}/garden-seedlings/{$fern->id}/edit";

        expect($seedlings->getUrl('edit', ['garden' => $rose, 'record' => $fern], isAbsolute: false, panel: 'nested'))->toBe($seedlingUrl)
            ->and($seedlings->getUrl('edit', ['garden' => $rose->id, 'record' => $fern->id], isAbsolute: false, panel: 'nested'))->toBe($seedlingUrl)
            ->and($seedlings->getUrl('edit', ['garden' => $rose, 'record' => $fern->id], isAbsolute: false, panel: 'nested'))->toBe($seedlingUrl)
            ->and($this->notes->getUrl('edit', ['larder' => $this->pantry, 'bin' => $this->bin, 'record' => $this->note], isAbsolute: false, panel: 'nested'))->toBe($this->noteUrl)
            ->and(fn (): string => $seedlings->getUrl('edit', ['record' => $fern], panel: 'nested'))->toThrow(MissingMandatoryParametersException::class, '"garden"');
    });

    it("guesses, only when asked, each ancestor the record reaches through its own associations, walking through an ancestor passed as a key and trusting it", function (): void {
        $note = $this->entityManager()->find(LarderBinNote::class, $this->note->id);
        $guess = fn (array $parameters): string => $this->notes->getUrl('edit', $parameters, isAbsolute: false, panel: 'nested', shouldGuessMissingParameters: true);

        expect($guess(['record' => $note]))->toBe($this->noteUrl)
            ->and($guess(['record' => $note, 'bin' => 'B1']))->toBe($this->noteUrl)
            ->and($guess(['record' => $note, 'bin' => 'C1']))->toBe("/nested/stocked-larders/{$this->pantry->id}/larder-bins/C1/larder-bin-notes/{$this->note->id}/edit")
            ->and(fn (): string => $this->notes->getUrl('edit', ['record' => $note], panel: 'nested'))->toThrow(MissingMandatoryParametersException::class, '"larder", "bin"');
    });

    it('guesses no parent across a many-to-many', function (): void {
        $rose = new Garden('Rose');
        $rose->addPlant($fern = new Plant('Fern'));
        $this->seed($rose, $fern);

        expect(fn (): string => ($this->resource)(GardenPlantResource::class)->getUrl('edit', ['record' => $this->entityManager()->find(Plant::class, $fern->id)], panel: 'nested', shouldGuessMissingParameters: true))
            ->toThrow(MissingMandatoryParametersException::class, '"gardens"');
    });

    it("lists itself on its parent's relationship page, else its view, else its edit page, else its index, carrying no tab", function (string $resource, array $parameters, string $url): void {
        $indexUrl = ($this->resource)($resource)->getIndexUrl($parameters, isAbsolute: false, panel: 'nested');

        expect($indexUrl)->toBe($url)
            ->and(parse_url($indexUrl, PHP_URL_QUERY))->toBeNull();
    })->with([
        'the relationship page' => [LarderBinResource::class, ['larder' => '4'], '/nested/stocked-larders/4/bins'],
        'the view page, before the edit page' => [GreenhouseVentSeedlingResource::class, ['vent' => 'v-1'], '/nested/greenhouse-vents/v-1'],
        'the edit page, before the index' => [GardenSeedlingResource::class, ['garden' => '7'], '/nested/gardens/7/edit'],
        "the index, without the parent's key" => [StockedCrockResource::class, ['larder' => '4'], '/nested/stocked-larders'],
    ]);

    it("names the parent page's record as the parameter missing from an index URL asked for without its parent", function (): void {
        expect(fn (): string => ($this->resource)(GardenSeedlingResource::class)->getIndexUrl(panel: 'nested'))
            ->toThrow(MissingMandatoryParametersException::class, 'Some mandatory parameters are missing ("record") to generate a URL for route "sylament.nested.resources.gardens.edit".');
    });
});

describe('navigation and search', function (): void {
    it("keeps its parent's sidebar item lit on its pages and draws no item of its own there, though it builds one when asked", function (): void {
        $rose = new Garden('Rose');
        $rose->seedlings->add($fern = new Seedling('Fern'));

        $fern->garden = $rose;
        $this->seed($rose, $fern);

        $page = $this->visit("/nested/gardens/{$rose->id}/garden-seedlings/{$fern->id}/edit");
        $items = ($this->resource)(GardenSeedlingResource::class)->getNavigationItems($this::getContainer()->get(PanelRegistry::class)->get('nested'));

        expect($page->filter(Selectors::ACTIVE_NAVIGATION_ITEM)->text())->toBe('Gardens')
            ->and($page->filter(Selectors::NAVIGATION_ITEM . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(static fn (Crawler $item): string => $item->text()))
            ->toContain('Gardens', 'Larders')
            ->not->toContain('Seedlings')
            ->and($items)->toHaveCount(1)
            ->and($items[0]->getLabel())->toBe('Seedlings');
    });

    it('answers no global search result URL, which would need its parent', function (): void {
        $this->seed($fern = new Seedling('Fern'));

        expect(($this->resource)(GardenSeedlingResource::class)->getGlobalSearchResultUrl($fern))->toBeNull();
    });
});
