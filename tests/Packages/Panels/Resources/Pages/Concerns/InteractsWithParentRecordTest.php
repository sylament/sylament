<?php

declare(strict_types=1);

use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Panels\Resources\AllotmentSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\GreenhouseVentSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Resources\LarderBinResource;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateGardenSeedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGardenPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGardenSeedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditLarderBinNote;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTerraceSeedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListGardenSeedlings;
use Sylament\Tests\Fixtures\Panels\Resources\TerraceSeedlingResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

beforeEach(function (): void {
    $this->rose = new Garden('Rose');
    $this->oak = new Garden('Oak');
    $seedlings = [];

    foreach ([[$this->rose, 'Fern'], [$this->rose, 'Sage'], [$this->rose, GardenSeedlingResource::HIDDEN_PREFIX . ' moss'], [$this->oak, 'Fennel']] as [$garden, $name]) {
        $seedlings[$name] = $seedling = new Seedling($name);
        $seedling->garden = $garden;
    }

    ['Fern' => $this->fern, 'Sage' => $this->sage, 'Fennel' => $this->fennel] = $seedlings;
    $this->hiddenMoss = $seedlings[GardenSeedlingResource::HIDDEN_PREFIX . ' moss'];

    $this->seed($this->rose, $this->oak, ...array_values($seedlings));

    $this->seedlingUrl = fn (Garden $garden, Seedling $seedling): string => "/nested/gardens/{$garden->id}/garden-seedlings/{$seedling->id}/edit";
    $this->crumbs = static fn (Crawler $page): array => $page->filter(SupportSelectors::BREADCRUMB)->each(static fn (Crawler $crumb): array => [$crumb->text(), $crumb->attr('href') === null ? null : parse_url($crumb->attr('href'), PHP_URL_PATH)]);
    $this->editSeedling = fn (Seedling $seedling): mixed => $this->createLiveComponent(EditGardenSeedling::class, ['record' => $seedling->id, 'parentRecords' => ['garden' => (string) $this->rose->id], 'panelId' => 'nested']);
});

describe('the first request', function (): void {
    // A tester assigns every prop before the page resolves anything, so only a plain GET shows the record resolved under its parent at mount.
    it('resolves the record under the parent its URL names on the first GET, carrying the parent keys as one prop, and 404s a record the parent does not hold', function (): void {
        $page = $this->visit(($this->seedlingUrl)($this->rose, $this->fern));

        expect($page->filter(Selectors::field('name'))->attr('value'))->toBe('Fern')
            ->and($this->renderedLiveProps($page)['parentRecords'])->toBe(['garden' => (string) $this->rose->id])
            ->and($page->filter(Selectors::PAGE)->count())->toBe(1)
            ->and($page->filter(Selectors::PAGE)->attr('garden'))->toBeNull()
            ->and(fn (): Crawler => $this->visit(($this->seedlingUrl)($this->oak, $this->fern)))->toThrow(NotFoundHttpException::class)
            ->and(fn (): Crawler => $this->visit(($this->seedlingUrl)($this->rose, $this->hiddenMoss)))->toThrow(NotFoundHttpException::class);
    });
});

describe('the chain', function (): void {
    beforeEach(function (): void {
        $this->pantry = new Larder('Pantry');
        $this->cellar = new Larder('Cellar');
        $this->pantry->addBin($bin = new LarderBin('B1'));
        $this->pantry->addBin($hiddenBin = new LarderBin(LarderBinResource::HIDDEN_PREFIX . '-9'));

        $this->cellar->addBin($cellarBin = new LarderBin('C1'));
        $bin->addNote($this->note = new LarderBinNote('Use first'));
        $hiddenBin->addNote($this->hiddenBinNote = new LarderBinNote('Spare'));
        $cellarBin->addNote($this->cellarNote = new LarderBinNote('Dry'));
        $this->seed($this->pantry, $this->cellar);

        $this->noteUrl = fn (Larder $larder, string $bin, LarderBinNote $note): string => "/nested/stocked-larders/{$larder->id}/larder-bins/{$bin}/larder-bin-notes/{$note->id}/edit";
    });

    it('resolves each ancestor root-first, scoped to the one above, refusing a key of another branch or one an ancestor hides at its own step', function (): void {
        $page = $this->visit(($this->noteUrl)($this->pantry, 'B1', $this->note));

        expect($page->filter(Selectors::field('text'))->attr('value'))->toBe('Use first')
            ->and(fn (): Crawler => $this->visit(($this->noteUrl)($this->pantry, 'C1', $this->cellarNote)))->toThrow(NotFoundHttpException::class, sprintf('Parent record [C1] not found for [%s].', LarderBinResource::class))
            ->and(fn (): Crawler => $this->visit(($this->noteUrl)($this->pantry, LarderBinResource::HIDDEN_PREFIX . '-9', $this->hiddenBinNote)))->toThrow(NotFoundHttpException::class, sprintf('Parent record [%s-9] not found for [%s].', LarderBinResource::HIDDEN_PREFIX, LarderBinResource::class))
            ->and(fn (): Crawler => $this->visit(($this->noteUrl)($this->pantry, 'B1', $this->cellarNote)))->toThrow(NotFoundHttpException::class);
    });

    it('costs one statement per ancestor and one for the record on a GET, its breadcrumbs included', function (): void {
        $this->visit(($this->noteUrl)($this->pantry, 'B1', $this->note));

        expect($this->statementsRun())->toHaveCount(3);
    });

    it('resolves the chain again on every live request, so a request after an ancestor moved away 404s', function (): void {
        $tester = $this->createLiveComponent(EditLarderBinNote::class, ['record' => $this->note->id, 'parentRecords' => ['larder' => (string) $this->pantry->id, 'bin' => 'B1'], 'panelId' => 'nested']);

        expect($tester->render()->crawler()->filter(Selectors::field('text'))->attr('value'))->toBe('Use first');

        $bin = $this->entityManager()->find(LarderBin::class, 'B1');
        $bin->larder = $this->entityManager()->find(Larder::class, $this->cellar->id);
        $this->entityManager()->flush();

        expect(fn (): mixed => $tester->refresh())->toThrow(NotFoundHttpException::class, sprintf('Parent record [B1] not found for [%s].', LarderBinResource::class));
    });

    it("answers the record above the page, the very instance the page's record points at, and none on a page nesting under nothing", function (): void {
        $page = ($this->editSeedling)($this->fern)->component();
        $this->seed($article = new Article('Draft'));
        $flatPage = $this->createLiveComponent(EditArticle::class, ['record' => $article->id])->component();

        expect($page->getParentRecord())->toBeInstanceOf(Garden::class)
            ->and($page->getParentRecord()?->id)->toBe($this->rose->id)
            ->and($page->getRecord()->garden)->toBe($page->getParentRecord())
            ->and($page->getParentResourceInstance())->toBeInstanceOf(GardenResource::class)
            ->and($flatPage->getParentRecord())->toBeNull()
            ->and($flatPage->getParentResourceInstance())->toBeNull();
    });
});

describe('the parent title', function (): void {
    it("titles the parent by its resource's title attribute, else by its record label, and refuses on a page nesting under nothing", function (): void {
        $terrace = new Terrace(estate: 1, number: 2);
        $terrace->seedlings->add($thyme = new Seedling('Thyme'));

        $thyme->terrace = $terrace;
        $this->seed($terrace, $thyme, $article = new Article('Draft'));

        $terracePage = $this->createLiveComponent(EditTerraceSeedling::class, ['record' => $thyme->id, 'parentRecords' => ['terrace' => RecordKey::of($this->entityManager(), $terrace)], 'panelId' => 'nested'])->component();
        $flatPage = $this->createLiveComponent(EditArticle::class, ['record' => $article->id])->component();

        expect(($this->editSeedling)($this->fern)->component()->getParentRecordTitle())->toBe('Rose')
            ->and($terracePage->getParentRecordTitle())->toBe('Terrace')
            ->and(fn (): string => $flatPage->getParentRecordTitle())->toThrow(LogicException::class, sprintf('The page [%s] belongs to a resource that names no parent, so it has no parent record to title.', EditArticle::class));
    });
});

describe('the scope', function (): void {
    it("resolves a mounted action's client-supplied record key through the parent scope, refusing a record of another parent or one the resource hides", function (Closure $seedling): void {
        $refused = $seedling($this);

        expect(fn (): mixed => ($this->editSeedling)($this->fern)->mountAction(['name' => 'delete', 'context' => ['recordKey' => (string) $refused->id]]))
            ->toThrow(NotFoundHttpException::class);

        ($this->editSeedling)($this->fern)
            ->mountAction(['name' => 'delete', 'context' => ['recordKey' => (string) $this->sage->id]])
            ->call('callMountedAction', ['arguments' => []]);

        $repository = $this->entityManager()->getRepository(Seedling::class);

        expect($repository->find($this->sage->id))->toBeNull()
            ->and($repository->find($this->fern->id))->not->toBeNull()
            ->and($repository->find($refused->id))->not->toBeNull();
    })->with([
        'another parent' => [static fn (object $test): Seedling => $test->fennel],
        'hidden' => [static fn (object $test): Seedling => $test->hiddenMoss],
    ]);
});

describe('the urls it mints', function (): void {
    it("trails each ancestor's listing and record before its own listing, unlinking a crumb whose URL the trail already spends", function (): void {
        expect(($this->crumbs)($this->visit(($this->seedlingUrl)($this->rose, $this->fern))))->toBe([
            ['Gardens', '/nested/gardens'],
            ['Rose', "/nested/gardens/{$this->rose->id}/edit"],
            ['Seedlings', null],
            ['Fern', ($this->seedlingUrl)($this->rose, $this->fern)],
            ['Edit', null],
        ]);
    });

    it('trails a chain three deep, linking each record crumb to its view, else its edit page, else nowhere', function (): void {
        $pantry = new Larder('Pantry');
        $pantry->addBin($bin = new LarderBin('B1'));

        $bin->addNote($note = new LarderBinNote('Use first'));
        $this->seed($pantry);

        expect(($this->crumbs)($this->visit("/nested/stocked-larders/{$pantry->id}/larder-bins/B1/larder-bin-notes/{$note->id}/edit")))->toBe([
            ['Larders', '/nested/stocked-larders'],
            ['Pantry', null],
            ['Larder Bins', "/nested/stocked-larders/{$pantry->id}/bins"],
            ['B1', "/nested/stocked-larders/{$pantry->id}/larder-bins/B1/edit"],
            ['Notes', null],
            ['Use first', "/nested/stocked-larders/{$pantry->id}/larder-bins/B1/larder-bin-notes/{$note->id}/edit"],
            ['Edit', null],
        ]);
    });

    it('fills its own record and parent keys into a URL of its resource, with nothing left to guess', function (): void {
        $page = ($this->editSeedling)($this->fern)->component();

        expect($page->getResourceUrl('edit', isAbsolute: false, panel: 'nested', shouldGuessMissingParameters: false))->toBe(($this->seedlingUrl)($this->rose, $this->fern))
            ->and($page->getResourceUrl(isAbsolute: false, panel: 'nested', shouldGuessMissingParameters: false))->toBe("/nested/gardens/{$this->rose->id}/edit");
    });

    it("lands a delete on the parent's page and a create on the new record's nested edit page, its parent keys threaded", function (): void {
        ($this->editSeedling)($this->fern)
            ->mountAction('delete')
            ->call('callMountedAction', ['arguments' => []])
            ->assertRedirect("/nested/gardens/{$this->rose->id}/edit");

        $create = $this->createLiveComponent(CreateGardenSeedling::class, ['parentRecords' => ['garden' => (string) $this->rose->id], 'panelId' => 'nested'])
            ->fillForm(['name' => 'Basil'])
            ->call('create');

        $basil = $this->entityManager()->getRepository(Seedling::class)->findOneBy(['name' => 'Basil']);

        $create->assertRedirect(($this->seedlingUrl)($this->rose, $basil));

        expect($basil->garden?->id)->toBe($this->rose->id)
            ->and($this->entityManager()->find(Seedling::class, $this->fern->id))->toBeNull();
    });
});

describe('the nested list', function (): void {
    it("lists only its parent's records its own query keeps, linking each to its nested edit page, searching and sorting within them", function (): void {
        $page = $this->visit("/nested/gardens/{$this->rose->id}/garden-seedlings");
        $editLinks = $page->filter(TablesSelectors::RECORD_ACTIONS . ' a[href]')->each(static fn (Crawler $link): ?string => parse_url((string) $link->attr('href'), PHP_URL_PATH));

        expect($page->filter(TablesSelectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing(['Fern', 'Sage'])
            ->and($editLinks)->toEqualCanonicalizing([($this->seedlingUrl)($this->rose, $this->fern), ($this->seedlingUrl)($this->rose, $this->sage)]);

        $this->createLiveComponent(ListGardenSeedlings::class, ['parentRecords' => ['garden' => (string) $this->rose->id], 'panelId' => 'nested'])
            ->searchTable('fe')
            ->assertCanSeeTableRecords([$this->fern])
            ->assertCanNotSeeTableRecords([$this->fennel, $this->sage])
            ->searchTable()
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$this->sage, $this->fern], inOrder: true)
            ->assertCountTableRecords(2);
    });
});

describe('creating under the parent', function (): void {
    it("creates a record in a modal joined to the page's parent, through the relationship the page answers", function (): void {
        $rose = $this->entityManager()->find(Garden::class, $this->rose->id);
        $rose->addPlant($tomato = new Plant('Tomato'));
        $this->seed($tomato, $rose);

        $this->createLiveComponent(EditGardenPlant::class, ['record' => $tomato->id, 'parentRecords' => ['gardens' => (string) $this->rose->id], 'panelId' => 'nested'])
            ->mountAction('create')
            ->set('mountedActionsData.0.name', 'Basil')
            ->call('callMountedAction', ['arguments' => []]);

        expect($this->reload($this->rose)->plants->map(static fn (Plant $plant): string => $plant->name)->toArray())->toEqualCanonicalizing(['Tomato', 'Basil']);
    });
});

describe('a parent of any width', function (): void {
    it("resolves a record under its parent's key, 404s it under another's, and mints that key into its URL, a guessed URL and the parent's crumb alike", function (string $resource, string $parameter, Closure $parents, string $parentPage): void {
        [$own, $other, $alsoSeeded] = $parents();
        $fern = new Seedling('Fern');
        $fern->{$parameter} = $own;
        $this->seed(...[...$alsoSeeded, $own, $other, $fern]);

        $registry = $this::getContainer()->get(ResourceRegistry::class);
        $child = $registry->get($resource);
        $url = $child->getUrl('edit', [$parameter => $own, 'record' => $fern], isAbsolute: false, panel: 'nested');
        $parentResource = $registry->get($child->getParentResource());
        $page = $this->visit($url);

        expect($page->filter(Selectors::field('name'))->attr('value'))->toBe('Fern')
            ->and(($this->crumbs)($page)[1][1])->toBe($parentResource->getUrl($parentPage, ['record' => $own], isAbsolute: false, panel: 'nested'))
            ->and($child->getUrl('edit', ['record' => $this->entityManager()->find(Seedling::class, $fern->id)], isAbsolute: false, panel: 'nested', shouldGuessMissingParameters: true))->toBe($url)
            ->and(fn (): Crawler => $this->visit($child->getUrl('edit', [$parameter => $other, 'record' => $fern], isAbsolute: false, panel: 'nested')))->toThrow(NotFoundHttpException::class);
    })->with([
        'two scalars' => [TerraceSeedlingResource::class, 'terrace', static fn (): array => [new Terrace(estate: 1, number: 2), new Terrace(estate: 1, number: 3), []], 'edit'],
        'an association plus a scalar' => [AllotmentSeedlingResource::class, 'allotment', static fn (): array => [new Allotment(new Garden('Rose'), number: 1), new Allotment(new Garden('Oak'), number: 1), []], 'edit'],
        'a derived identity' => [GreenhouseVentSeedlingResource::class, 'vent', static function (): array {
            $greenhouses = [new Greenhouse(Uuid::v7()), new Greenhouse(Uuid::v7())];

            return [new GreenhouseVent($greenhouses[0]), new GreenhouseVent($greenhouses[1]), $greenhouses];
        }, 'view'],
    ]);
});
