<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Entity\TramCrate;
use Sylament\Tests\Fixtures\Entity\TramDepot;
use Sylament\Tests\Fixtures\Entity\TramParcel;
use Sylament\Tests\Fixtures\Entity\TramTariff;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AssociableRelationTable;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\DomCrawler\Crawler;

describe('over a garden', function (): void {
    beforeEach(function (): void {
        $this->kew = new Garden('Kew');
        $this->wisley = new Garden('Wisley');

        foreach (['bramble' => $this->kew, 'thistle' => $this->kew, 'clover' => $this->wisley, 'nettle' => null, 'yarrow' => null] as $name => $garden) {
            $this->{$name} = new Seedling(ucfirst($name));
            $this->{$name}->garden = $garden;
        }

        // Seeded against the alphabet, so an option list in title order is not the identifiers' order.
        $this->seed($this->kew, $this->wisley, $this->bramble, $this->thistle, $this->yarrow, $this->nettle, $this->clover);

        $this->table = fn (array $variants = []): Testable => $this->createLiveComponent(
            AssociableRelationTable::class,
            AssociableRelationTable::props($this->entityManager(), $this->kew, 'seedlings', variants: $variants),
        );
        $this->mounted = fn (array $variants = []): Testable => ($this->table)($variants)->mountAction(TestAction::make('associate')->table());
        $this->seedlingsOf = function (Garden $garden): array {
            $names = array_map(static fn (Seedling $seedling): string => $seedling->name, $this->reload($garden)->seedlings->toArray());
            sort($names);

            return $names;
        };
        $this->key = static fn (Seedling $seedling): string => (string) $seedling->id;
    });

    describe('the modal', function (): void {
        it('opens from a gray "Associate" header trigger onto an lg "Associate Seedling" modal whose one field is a required record select, submitted by "Associate" or "Associate & associate another"', function (): void {
            $tester = ($this->table)();
            $trigger = $tester->render()->crawler()->filter(Selectors::trigger('associate') . SupportSelectors::GRAY);
            $modal = $tester->mountAction(TestAction::make('associate')->table())->render()->crawler();

            expect($trigger->text())->toBe('Associate')
                ->and($modal->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Associate Seedling')
                ->and($modal->filter(SupportSelectors::OPEN_MODAL . ' ' . SupportSelectors::modalWidth('lg'))->count())->toBe(1)
                ->and($modal->filter(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::FIELD_WRAPPER)->count())->toBe(1)
                ->and($modal->filter(Selectors::RECORD_SELECT_LABEL . SupportSelectors::SCREEN_READER_ONLY)->text())->toBe('Record')
                ->and($modal->filter(Selectors::RECORD_SELECT . '[aria-required="true"]:not([multiple])')->count())->toBe(1)
                ->and($modal->filter(SupportSelectors::MODAL_SUBMIT)->text())->toBe('Associate')
                ->and($modal->filter(SupportSelectors::MODAL_FOOTER_ACTIONS)->each(static fn (Crawler $button): string => $button->text()))->toBe(['Associate', 'Associate & associate another', 'Cancel']);
        });

        it('offers no "Associate & associate another" under associateAnother(false)', function (): void {
            $modal = ($this->mounted)(['noAssociateAnother'])->render()->crawler();

            expect($modal->filter(SupportSelectors::MODAL_FOOTER_ACTIONS)->each(static fn (Crawler $button): string => $button->text()))->toBe(['Associate', 'Cancel']);
        });

        it('renders a multiple select under multiple(), and a single one when multiple() answers false through a closure', function (string $variant, int $multiple): void {
            $modal = ($this->mounted)([$variant])->render()->crawler();

            expect($modal->filter(Selectors::RECORD_SELECT)->count())->toBe(1)
                ->and($modal->filter(Selectors::RECORD_SELECT . '[multiple]')->count())->toBe($multiple);
        })->with([
            'multiple()' => ['multiple', 1],
            'a closure answering false' => ['singleByClosure', 0],
        ]);

        it('refuses a submit that picks no record', function (): void {
            ($this->table)()
                ->callAction(TestAction::make('associate')->table())
                ->assertHasActionErrors(['recordId' => RequiredValue::class]);

            expect(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Thistle']);
        });
    });

    describe('the options', function (): void {
        it("lists nothing until the user types, then every seedling not the garden's own, another garden's included, whose title matches, in title order", function (): void {
            $tester = ($this->mounted)();

            expect($tester->render()->crawler()->filter(Selectors::RECORD_SELECT . ' ' . FormsSelectors::OPTION)->count())->toBe(0)
                ->and($this->recordSelectSearch($tester, ''))->toBe([($this->key)($this->clover) => 'Clover', ($this->key)($this->nettle) => 'Nettle', ($this->key)($this->yarrow) => 'Yarrow'])
                ->and($this->recordSelectSearch($tester, 'arr'))->toBe([($this->key)($this->yarrow) => 'Yarrow']);
        });

        it('lists the candidates in the select as it opens once preloadRecordSelect() says so', function (): void {
            $modal = ($this->mounted)(['preloaded'])->render()->crawler();

            expect($modal->filter(Selectors::RECORD_SELECT . ' ' . FormsSelectors::OPTION)->each(static fn (Crawler $option): string => $option->text()))->toBe(['Clover', 'Nettle', 'Yarrow']);
        });

        it('labels what the user picked by its title, one pick or several', function (array $variants, array|string $picks, array $labels): void {
            $modal = ($this->mounted)($variants)->setActionData(['recordId' => $picks])->render()->crawler();

            expect($modal->filter(Selectors::RECORD_SELECT . ' option[selected]')->each(static fn (Crawler $option): string => $option->text()))->toEqualCanonicalizing($labels);
        })->with([
            'one pick' => [[], fn (): string => ($this->key)($this->nettle), ['Nettle']],
            'two picks' => [['multiple'], fn (): array => [($this->key)($this->nettle), ($this->key)($this->clover)], ['Nettle', 'Clover']],
        ]);

        it("caps the options at the select's optionsLimit, which a recordSelect() closure reshapes, returning the select or nothing", function (string $variant): void {
            expect($this->recordSelectSearch(($this->mounted)([$variant]), ''))->toBe([($this->key)($this->clover) => 'Clover']);
        })->with(['limitedToOne', 'limitedToOneInPlace']);

        it('carries the select a recordSelect() closure returns in place of its own', function (): void {
            $modal = ($this->mounted)(['swappedSelect'])->render()->crawler();

            expect($modal->filter(Selectors::RECORD_SELECT . ' ' . FormsSelectors::OPTION)->each(static fn (Crawler $option): string => $option->text()))->toBe(['Spare']);
        });

        it("titles the options through the table's recordTitle() closure, still in the title attribute's order, and projects the title attribute without loading a candidate otherwise", function (): void {
            $titled = array_values($this->recordSelectSearch(($this->mounted)(['tableTitleClosure']), ''));
            $hydratedClover = $this->entityManager()->getUnitOfWork()->tryGetById($this->clover->id, Seedling::class);

            $projected = array_values($this->recordSelectSearch(($this->mounted)(), ''));

            expect($titled)->toBe(['CLOVER', 'NETTLE', 'YARROW'])
                ->and($hydratedClover)->toBeInstanceOf(Seedling::class)
                ->and($projected)->toBe(['Clover', 'Nettle', 'Yarrow'])
                ->and($this->entityManager()->getUnitOfWork()->tryGetById($this->clover->id, Seedling::class))->toBeFalse();
        });

        it("titles the options through the action's own recordTitle() closure by loading each candidate, and projects an action's own title attribute over a table's recordTitle() closure", function (): void {
            $titled = array_values($this->recordSelectSearch(($this->mounted)(['actionTitleClosure']), ''));
            $hydratedClover = $this->entityManager()->getUnitOfWork()->tryGetById($this->clover->id, Seedling::class);

            $projected = array_values($this->recordSelectSearch(($this->mounted)(['tableTitleClosure', 'actionTitleAttribute']), ''));

            expect($titled)->toBe(['Titled: Clover', 'Titled: Nettle', 'Titled: Yarrow'])
                ->and($hydratedClover)->toBeInstanceOf(Seedling::class)
                ->and($projected)->toBe(['Clover', 'Nettle', 'Yarrow'])
                ->and($this->entityManager()->getUnitOfWork()->tryGetById($this->clover->id, Seedling::class))->toBeFalse();
        });

        it('keeps the order a candidate query gives itself over the title order', function (): void {
            expect(array_values($this->recordSelectSearch(($this->mounted)(['selfOrdered']), '')))->toBe(['Yarrow', 'Nettle', 'Clover']);
        });

        it('accepts a pick past the options limit by asking the candidate query, and refuses it once preloaded, whose options are the whole choice', function (array $variants, bool $refused): void {
            $tester = ($this->table)(['limitedToOne', ...$variants])->callAction(TestAction::make('associate')->table(), data: ['recordId' => ($this->key)($this->yarrow)]);

            if ($refused) {
                $tester->assertHasActionErrors(['recordId']);
            }

            expect(($this->seedlingsOf)($this->kew))->toBe($refused ? ['Bramble', 'Thistle'] : ['Bramble', 'Thistle', 'Yarrow']);
        })->with([
            'searched' => [[], false],
            'preloaded' => [['preloaded'], true],
        ]);

        it('hands recordSelectOptionsQuery() the candidate query and the search, null outside a search, and offers only what the closure keeps', function (): void {
            $tester = ($this->mounted)(['scoped']);
            $searched = $this->recordSelectSearch($tester, 'e');
            $searchLog = $this::getContainer()->get(HookLog::class)->hooks;

            $tester->setActionData(['recordId' => ($this->key)($this->nettle)])->callMountedAction();

            expect($searched)->toBe([($this->key)($this->clover) => 'Clover'])
                ->and($searchLog)->toBe(['options query: e'])
                ->and(array_unique($this::getContainer()->get(HookLog::class)->hooks))->toBe(['options query: null']);

            $tester->assertHasActionErrors(['recordId']);

            expect(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Thistle']);
        });

        it('lowers both sides of the search on PostgreSQL alone by default, and on every platform once forceSearchCaseInsensitive() says so', function (array $variants, bool $forced): void {
            $this->recordSelectSearch(($this->mounted)($variants), 'nettle');
            $searches = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, ' LIKE ')));

            expect($searches)->toHaveCount(1)
                ->and(str_contains($searches[0], 'LOWER('))->toBe(match (TestPlatform::current()) {
                    TestPlatform::PostgreSql => true,
                    TestPlatform::MySql, TestPlatform::Sqlite => $forced,
                });
        })->with([
            'by default' => [[], false],
            'forced' => [['caseless'], true],
        ]);
    });

    describe('submitting', function (): void {
        it('points the picked seedling at the garden in one flush, moving it from another garden, toasts "Associated", closes the modal and lists the row', function (string $picked, array $kew, array $wisley): void {
            $tester = ($this->table)()->callAction(TestAction::make('associate')->table(), data: ['recordId' => ($this->key)($this->{$picked})]);
            $commits = array_count_values($this->statementsRun())['"COMMIT"'] ?? 0;

            $tester->assertNotified('Associated')
                ->assertActionNotMounted();

            expect($commits)->toBe(1)
                ->and($tester->render()->crawler()->filter(TablesSelectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($kew)
                ->and(($this->seedlingsOf)($this->kew))->toBe($kew)
                ->and(($this->seedlingsOf)($this->wisley))->toBe($wisley);
        })->with([
            'an orphan' => ['nettle', ['Bramble', 'Nettle', 'Thistle'], ['Clover']],
            "another garden's" => ['clover', ['Bramble', 'Clover', 'Thistle'], []],
        ]);

        it('associates every seedling a multiple() select picks, one flush each, and toasts "Associated"', function (): void {
            ($this->table)(['multiple'])
                ->callAction(TestAction::make('associate')->table(), data: ['recordId' => [($this->key)($this->nettle), ($this->key)($this->yarrow)]])
                ->assertNotified('Associated');

            expect(array_count_values($this->statementsRun())['"COMMIT"'] ?? 0)->toBe(2)
                ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle', 'Yarrow'])
                ->and(($this->seedlingsOf)($this->wisley))->toBe(['Clover']);
        });

        it('associates the one key a single select picks when multiple() answers false through a closure', function (): void {
            ($this->table)(['singleByClosure'])
                ->callAction(TestAction::make('associate')->table(), data: ['recordId' => ($this->key)($this->nettle)])
                ->assertNotified('Associated');

            expect(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle']);
        });

        it("refuses a picked seedling that is already the garden's with a validation error", function (): void {
            ($this->table)()
                ->callAction(TestAction::make('associate')->table(), data: ['recordId' => ($this->key)($this->bramble)])
                ->assertHasActionErrors(['recordId']);

            expect(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Thistle']);
        });

        it('associates through "Associate & associate another", then empties the select, keeps the modal open and stops offering what it associated', function (): void {
            $tester = ($this->mounted)()
                ->setActionData(['recordId' => ($this->key)($this->nettle)])
                ->callMountedAction(['another' => true])
                ->assertNotified('Associated')
                ->assertActionMounted(TestAction::make('associate')->table())
                ->assertActionDataSet(['recordId' => null]);

            expect($this->recordSelectSearch($tester, ''))->toBe([($this->key)($this->clover) => 'Clover', ($this->key)($this->yarrow) => 'Yarrow'])
                ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle']);
        });

        it('hands using() the relationship and the picked record instead of associating it, and still toasts', function (): void {
            ($this->table)(['recordingAssociate'])
                ->callAction(TestAction::make('associate')->table(), data: ['recordId' => ($this->key)($this->nettle)])
                ->assertNotified('Associated');

            expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['associate Nettle to seedlings'])
                ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Thistle']);
        });

        it('refuses a table that declares no relationship', function (): void {
            expect(fn (): Testable => ($this->table)(['queryOnly', 'permissive'])->callAction(TestAction::make('associate')->table(), data: ['recordId' => ($this->key)($this->nettle)]))
                ->toThrow(LogicException::class, 'The action [associate] associates records through a relationship, which only a table declaring relationship() can supply.')
                ->and($this->reload($this->nettle)->garden)->toBeNull();
        });
    });

    describe('a submission the select does not check', function (): void {
        it('refuses a key the candidate query does not offer, writing nothing', function (array $variants, string $key): void {
            expect(fn (): Testable => ($this->table)(['permissive', ...$variants])->callAction(TestAction::make('associate')->table(), data: ['recordId' => $key]))
                ->toThrow(LogicException::class, 'The action [associate] was asked to associate a record its select never offered')
                ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Thistle']);
        })->with([
            'malformed' => [[], 'nettle'],
            "outside recordSelectOptionsQuery()'s scope" => [['scoped'], fn (): string => ($this->key)($this->nettle)],
            'already associated' => [[], fn (): string => ($this->key)($this->bramble)],
        ]);

        it('refuses a submission naming no record', function (): void {
            expect(fn (): Testable => ($this->table)(['permissive'])->callAction(TestAction::make('associate')->table()))
                ->toThrow(LogicException::class, 'The action [associate] was submitted with no record to associate.')
                ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Thistle']);
        });
    });
});

describe('over the shapes a one-to-many takes', function (): void {
    it('searches the fields recordSelectSearchColumns() names instead of the title, a string-keyed list alike', function (string $variant): void {
        $this->seed($depot = new TramDepot('Holloway'), new TramCrate(bay: 1, slot: 1, label: 'North', contents: 'Fuses'), new TramCrate(bay: 1, slot: 2, label: 'South', contents: 'Lamps'));

        $tester = $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $depot, 'crates', 'label', [$variant]))
            ->mountAction(TestAction::make('associate')->table());

        expect(array_values($this->recordSelectSearch($tester, 'Fus')))->toBe(['North'])
            ->and($this->recordSelectSearch($tester, 'North'))->toBe([]);
    })->with(['searchContents', 'searchContentsKeyed']);

    it('refuses a search column the target does not map, naming the action, the column and the class', function (): void {
        $this->seed($depot = new TramDepot('Holloway'), new TramCrate(bay: 1, slot: 1, label: 'North'));

        $tester = $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $depot, 'crates', 'label', ['searchUnmapped']))
            ->mountAction(TestAction::make('associate')->table());

        expect(fn (): array => $this->recordSelectSearch($tester, 'red'))
            ->toThrow(LogicException::class, sprintf('The action [associate] searches [colour] on [%s], which maps no such field.', TramCrate::class));
    });

    it("offers what the owner lacks, another owner's included, and associates it, whatever the owner's keys and the child's", function (Closure $scene, string $titleAttribute): void {
        [$owner, $property, $held, $orphan, $others] = $scene();
        $this->seed($owner, $held, $orphan, ...$others);
        $key = fn (object $record): string => RecordKey::of($this->entityManager(), $record);

        $tester = $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $owner, $property, $titleAttribute))
            ->mountAction(TestAction::make('associate')->table());

        expect(array_keys($this->recordSelectSearch($tester, '')))->toEqualCanonicalizing([$key($orphan), $key($others[0])]);

        $tester->setActionData(['recordId' => $key($others[0])])->callMountedAction()->assertNotified('Associated');

        expect(array_map($key, $this->reload($owner)->{$property}->toArray()))->toEqualCanonicalizing([$key($held), $key($others[0])]);
    })->with([
        'an owner keyed by scalar fields' => [static function (): array {
            $terrace = new Terrace(estate: 4, number: 1);
            $held = new Seedling('Held');
            $held->terrace = $terrace;

            $moved = new Seedling('Moved');
            $moved->terrace = new Terrace(estate: 4, number: 2);

            $other = $moved->terrace;

            return [$terrace, 'seedlings', $held, new Seedling('Orphan'), [$moved, $other]];
        }, 'name'],
        'an owner keyed by an association plus a scalar' => [static function (): array {
            $allotment = new Allotment(new Garden('Kew'), number: 1);
            $held = new Seedling('Held');
            $held->allotment = $allotment;

            $moved = new Seedling('Moved');
            $moved->allotment = new Allotment($allotment->garden, number: 2);

            $other = $moved->allotment;

            return [$allotment, 'seedlings', $held, new Seedling('Orphan'), [$moved, $other]];
        }, 'name'],
        'a child keyed by scalar fields' => [static function (): array {
            $depot = new TramDepot('Holloway');
            $held = new TramCrate(bay: 7, slot: 2, label: 'Held');
            $held->depot = $depot;

            $moved = new TramCrate(bay: 8, slot: 2, label: 'Moved');
            $moved->depot = new TramDepot('Acton');

            $other = $moved->depot;

            return [$depot, 'crates', $held, new TramCrate(bay: 7, slot: 3, label: 'Orphan'), [$moved, $other]];
        }, 'label'],
        'a child keyed by an association plus a scalar' => [static function (): array {
            $depot = new TramDepot('Holloway');
            $held = new TramParcel($seven = new Invoice(7), line: 2, label: 'Held');
            $held->depot = $depot;

            $moved = new TramParcel(new Invoice(8), line: 2, label: 'Moved');
            $moved->depot = new TramDepot('Acton');

            $other = $moved->depot;

            return [$depot, 'parcels', $held, new TramParcel($seven, line: 3, label: 'Orphan'), [$moved, $other]];
        }, 'label'],
    ]);
});

describe('a composite submission longer than one statement holds', function (): void {
    it('associates every key, 500 keys a statement', function (Closure $children, string $relation): void {
        $records = $children();
        $this->seed($depot = new TramDepot('Holloway'), ...$records);
        $keys = array_map(fn (object $child): string => RecordKey::of($this->entityManager(), $child), $records);

        $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $depot, $relation, 'label', ['multiple']))
            ->callAction(TestAction::make('associate')->table(), data: ['recordId' => $keys])
            ->assertNotified('Associated');

        expect(max(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), $this->statementsRun())))->toBe(500 * 2 + 1)
            ->and($this->reload($depot)->{$relation})->toHaveCount(501);
    })->with([
        'scalar fields' => [static fn (): array => array_map(static fn (int $slot): TramCrate => new TramCrate(bay: 1, slot: $slot), range(1, 501)), 'crates'],
        'an association plus a scalar' => [static function (): array {
            $invoice = new Invoice(1);

            return array_map(static fn (int $line): TramParcel => new TramParcel($invoice, line: $line), range(1, 501));
        }, 'parcels'],
    ]);

    it('refuses two spellings of one key that land in different statements, as one statement refuses them', function (): void {
        $tariffs = array_map(static fn (int $zone): TramTariff => new TramTariff('1.50', "z{$zone}"), range(1, 500));
        $this->seed($depot = new TramDepot('Holloway'), ...$tariffs);
        $keys = array_map(fn (TramTariff $tariff): string => RecordKey::of($this->entityManager(), $tariff), $tariffs);

        expect($keys[0])->toBe('1=2E50~z1')
            ->and(fn (): Testable => $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $depot, 'tariffs', 'label', ['multiple', 'permissive']))
                ->callAction(TestAction::make('associate')->table(), data: ['recordId' => [...$keys, '1=2E5~z1']]))
            ->toThrow(LogicException::class, 'The action [associate] was asked to associate a record its select never offered')
            ->and($this->reload($depot)->tariffs)->toHaveCount(0);
    });
});
