<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\CachedLantern;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\GardenPlot;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\LanternShelf;
use Sylament\Tests\Fixtures\Entity\LanternStand;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Entity\TramCrate;
use Sylament\Tests\Fixtures\Entity\TramDepot;
use Sylament\Tests\Fixtures\Entity\TramDialGauge;
use Sylament\Tests\Fixtures\Entity\TramGauge;
use Sylament\Tests\Fixtures\Entity\TramParcel;
use Sylament\Tests\Fixtures\Entity\TramTariff;
use Sylament\Tests\Fixtures\Entity\TramToaster;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AssociableRelationTable;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Sylament\Tests\Infrastructure\Doctrine\FailingWrite;

beforeEach(function (): void {
    // The temporary table a joined target's update goes through is created and dropped in
    // each platform's own DDL, so only the statements that write rows are compared.
    $this->writes = fn (): array => array_values(array_filter(
        $this->statementsRun(),
        static fn (string $sql): bool => preg_match('/^(INSERT|UPDATE|DELETE|"START TRANSACTION"|"COMMIT"|"ROLLBACK")/', $sql) === 1,
    ));
});

describe('over a garden', function (): void {
    beforeEach(function (): void {
        $this->kew = new Garden('Kew');
        $this->wisley = new Garden('Wisley');

        foreach (['bramble' => $this->kew, 'thistle' => $this->kew, 'nettle' => $this->kew, 'clover' => $this->wisley] as $name => $garden) {
            $this->{$name} = new Seedling(ucfirst($name));
            $this->{$name}->garden = $garden;
        }

        $this->seed($this->kew, $this->wisley, $this->bramble, $this->thistle, $this->nettle, $this->clover);

        $this->table = fn (array $variants = []): Testable => $this->createLiveComponent(
            AssociableRelationTable::class,
            AssociableRelationTable::props($this->entityManager(), $this->kew, 'seedlings', variants: $variants),
        );
        $this->seedlingsOf = function (Garden $garden): array {
            $names = array_map(static fn (Seedling $seedling): string => $seedling->name, $this->reload($garden)->seedlings->toArray());
            sort($names);

            return $names;
        };
    });

    it('puts a danger "Dissociate selected" with the x-mark in the toolbar, confirmed in a modal headed by the plural label, with the x-mark and a "Dissociate" submit', function (): void {
        $tester = ($this->table)();
        $trigger = $tester->render()->crawler()->filter(Selectors::trigger('dissociate') . SupportSelectors::color('danger') . TablesSelectors::BULK_ONLY);
        $modal = $tester->selectTableRecords([$this->bramble])->mountAction(TestAction::make('dissociate')->table()->bulk())->render()->crawler();

        expect($trigger->filter(SupportSelectors::ICON)->text())->toBe('x-mark-20-solid')
            ->and(mb_trim(str_replace('x-mark-20-solid', '', $trigger->text())))->toBe('Dissociate selected')
            ->and($modal->filter(Selectors::MODAL_HEADING)->text())->toBe('Dissociate selected Seedlings')
            ->and($modal->filter(Selectors::MODAL_DESCRIPTION)->text())->toBe('Are you sure you would like to do this?')
            ->and($modal->filter(Selectors::MODAL_ICON)->text())->toBe('x-mark')
            ->and($modal->filter(Selectors::MODAL_SUBMIT . SupportSelectors::color('danger'))->text())->toBe('Dissociate');
    });

    it("nulls every selected seedling's garden, keeping the seedlings, toasts \"Dissociated\" and clears the selection", function (array $variants, Closure $writes): void {
        $tester = ($this->table)($variants)->selectTableRecords([$this->bramble, $this->thistle]);
        $selected = $tester->component()->selectedTableRecordKeys;

        $tester->callAction(TestAction::make('dissociate')->table()->bulk())->assertNotified('Dissociated');

        expect(($this->writes)())->toBe($writes())
            ->and($selected)->toHaveCount(2)
            ->and($tester->component()->selectedTableRecordKeys)->toBe([])
            ->and(($this->seedlingsOf)($this->kew))->toBe(['Nettle'])
            ->and(($this->seedlingsOf)($this->wisley))->toBe(['Clover'])
            ->and($this->reload($this->bramble)->garden)->toBeNull()
            ->and($this->reload($this->bramble)->name)->toBe('Bramble');
    })->with([
        'reading the selected records, in one flush' => [[], static fn (): array => [
            '"START TRANSACTION"',
            'UPDATE Seedling SET garden_id = ? WHERE id = ?',
            'UPDATE Seedling SET garden_id = ? WHERE id = ?',
            '"COMMIT"',
        ]],
        'by their keys, in one statement' => [['noFetch'], static fn (): array => [
            'UPDATE Seedling SET garden_id = NULL WHERE id IN (?, ?)',
        ]],
    ]);

    it('leaves a selected seedling of another garden as it is, since the selection is read through the relationship', function (array $variants): void {
        ($this->table)($variants)
            ->selectTableRecords([$this->bramble, $this->clover])
            ->callAction(TestAction::make('dissociate')->table()->bulk())
            ->assertNotified('Dissociated');

        expect(($this->seedlingsOf)($this->kew))->toBe(['Nettle', 'Thistle'])
            ->and(($this->seedlingsOf)($this->wisley))->toBe(['Clover']);
    })->with([
        'reading the selected records' => [[]],
        'by their keys' => [['noFetch']],
    ]);

    it('dissociates nothing when one selected seedling fails to write, toasting that the whole selection failed', function (): void {
        FailingWrite::afterUpdates($this->entityManager(), Seedling::class, 1);

        ($this->table)()
            ->selectTableRecords([$this->bramble, $this->thistle])
            ->callAction(TestAction::make('dissociate')->table()->bulk())
            ->assertNotified('Failed to dissociate 2 records');

        expect(($this->writes)())->toContain('"ROLLBACK"')
            ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle']);
    });

    it('counts what a using() closure reports done in the failure title', function (): void {
        ($this->table)(['partialBulk'])
            ->selectTableRecords([$this->bramble, $this->thistle])
            ->callAction(TestAction::make('dissociate')->table()->bulk())
            ->assertNotified('Dissociated 1 of 2');
    });

    it('hands using() the selected records instead of dissociating them', function (): void {
        ($this->table)(['recordingBulk'])
            ->selectTableRecords([$this->bramble, $this->thistle])
            ->callAction(TestAction::make('dissociate')->table()->bulk())
            ->assertNotified('Dissociated');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['dissociate Bramble, Thistle'])
            ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle']);
    });

    it('toasts a failure for a selected row that is not an entity', function (): void {
        ($this->table)(['arrayRecords'])
            ->selectTableRecords(['0'])
            ->callAction(TestAction::make('dissociate')->table()->bulk())
            ->assertNotified('Failed to dissociate 1 records');

        expect(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle']);
    });

    it('refuses a selected row that is not a record key by key, as the developer error it is', function (): void {
        expect(fn (): Testable => ($this->table)(['arrayRecords', 'noFetch'])
            ->selectTableRecords(['0'])
            ->callAction(TestAction::make('dissociate')->table()->bulk()))
            ->toThrow(LogicException::class, 'DissociateBulkAction dissociates by key from record keys, got array.')
            ->and(($this->seedlingsOf)($this->kew))->toBe(['Bramble', 'Nettle', 'Thistle']);
    });

    it('refuses a table that declares no relationship', function (): void {
        expect(fn (): Testable => ($this->table)(['queryOnly'])
            ->selectTableRecords([$this->bramble])
            ->callAction(TestAction::make('dissociate')->table()->bulk()))
            ->toThrow(LogicException::class, 'The action [dissociate] dissociates selected records through a relationship, which only a table declaring relationship() can supply.')
            ->and($this->reload($this->bramble)->garden?->id)->toBe($this->kew->id);
    });
});

it('refuses a relationship it cannot dissociate before either arm writes, naming why', function (array $scene, array $variants): void {
    [$owner, $relation, $selected, $message, $untouched] = $scene;

    expect(fn (): Testable => $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $owner, $relation, null, $variants))
        ->selectTableRecords([$selected])
        ->callAction(TestAction::make('dissociate')->table()->bulk()))
        ->toThrow(LogicException::class, $message)
        ->and(($this->writes)())->toBe([])
        ->and($untouched())->toBeTrue();
})->with([
    'a one-to-many removing its orphans' => [function (): array {
        $plot = new GardenPlot();
        $plot->garden = new Garden('Kew');

        $kew = $plot->garden;
        $this->seed($kew, $plot);

        return [$kew, 'plots', $plot, sprintf('[%s::$plots] declares orphanRemoval, so a record removed from it is deleted rather than dissociated', Garden::class), fn (): bool => $this->reload($plot)->garden?->id === $kew->id];
    }],
    'the owning side of a many-to-many' => [function (): array {
        ($kew = new Garden('Kew'))->addPlant($rock = new Plant('Rock'));
        $this->seed($kew, $rock);

        return [$kew, 'plants', $rock, 'The action [dissociate] dissociates a one-to-many; [plants] is a many-to-many — use DetachBulkAction.', fn (): bool => count($this->reload($kew)->plants) === 1];
    }],
    'the inverse side of a many-to-many' => [function (): array {
        ($kew = new Garden('Kew'))->addPlant($rock = new Plant('Rock'));
        $this->seed($kew, $rock);

        return [$rock, 'gardens', $kew, 'The action [dissociate] dissociates a one-to-many; [gardens] is a many-to-many — use DetachBulkAction.', fn (): bool => count($this->reload($kew)->plants) === 1];
    }],
])->with([
    'reading the selected records' => [[]],
    'by their keys' => [['noFetch']],
]);

it('nulls the selected keys through the one statement their foreign key and their class call for', function (array $scene, array $writes): void {
    [$owner, $relation, $child, $property] = $scene;

    $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $owner, $relation, variants: ['noFetch']))
        ->selectTableRecords([$child])
        ->callAction(TestAction::make('dissociate')->table()->bulk())
        ->assertNotified('Dissociated');

    expect(($this->writes)())->toBe($writes)
        ->and($this->reload($child)->{$property})->toBeNull();
})->with([
    'a foreign key of one column, in DQL' => [function (): array {
        $seedling = new Seedling('Bramble');
        $seedling->garden = new Garden('Kew');

        $kew = $seedling->garden;
        $this->seed($kew, $seedling);

        return [$kew, 'seedlings', $seedling, 'garden'];
    }, ['UPDATE Seedling SET garden_id = NULL WHERE id IN (?)']],
    'a foreign key onto two scalar fields, in SQL' => [function (): array {
        $seedling = new Seedling('Bramble');
        $seedling->terrace = new Terrace(estate: 4, number: 1);

        $terrace = $seedling->terrace;
        $this->seed($terrace, $seedling);

        return [$terrace, 'seedlings', $seedling, 'terrace'];
    }, ['UPDATE Seedling SET terrace_estate = NULL, terrace_number = NULL WHERE id IN (?)']],
    'a foreign key onto an association plus a scalar, in SQL' => [function (): array {
        $seedling = new Seedling('Bramble');
        $seedling->allotment = new Allotment(new Garden('Kew'), number: 1);

        $allotment = $seedling->allotment;
        $this->seed($allotment, $seedling);

        return [$allotment, 'seedlings', $seedling, 'allotment'];
    }, ['UPDATE Seedling SET allotment_garden = NULL, allotment_number = NULL WHERE id IN (?)']],
    'the leaf of a single-table hierarchy, by its discriminator' => [function (): array {
        $dial = new TramDialGauge('Pressure');
        $dial->depot = new TramDepot('Holloway');

        $depot = $dial->depot;
        $this->seed($depot, $dial, new TramGauge('Spare'));

        return [$depot, 'dials', $dial, 'depot'];
    }, ["UPDATE TramGauge SET depot_id = NULL WHERE (id IN (?)) AND kind IN ('dial')"]],
    "the leaf of a joined hierarchy whose root holds the foreign key, through Doctrine's temporary table" => [function (): array {
        $toaster = new TramToaster('Canteen');
        $toaster->depot = new TramDepot('Holloway');

        $depot = $toaster->depot;
        $this->seed($depot, $toaster);

        return [$depot, 'toasters', $toaster, 'depot'];
    }, [
        'INSERT INTO TramAppliance_id_tmp (id) SELECT p0.id FROM TramToaster p0 INNER JOIN TramAppliance a0_ ON p0.id = a0_.id WHERE a0_.id IN (?)',
        'UPDATE TramAppliance SET depot_id = NULL WHERE (id) IN (SELECT id FROM TramAppliance_id_tmp)',
    ]],
    'a child keyed by scalar fields' => [function (): array {
        $crate = new TramCrate(bay: 7, slot: 2, label: 'Fuses');
        $crate->depot = new TramDepot('Holloway');

        $depot = $crate->depot;
        $this->seed($depot, $crate, new TramCrate(bay: 7, slot: 3));

        return [$depot, 'crates', $crate, 'depot'];
    }, ['UPDATE TramCrate SET depot_id = NULL WHERE (bay = ? AND slot = ?)']],
    'a child keyed by an association plus a scalar' => [function (): array {
        $parcel = new TramParcel(new Invoice(7), line: 2, label: 'Fuses');
        $parcel->depot = new TramDepot('Holloway');

        $depot = $parcel->depot;
        $this->seed($depot, $parcel);

        return [$depot, 'parcels', $parcel, 'depot'];
    }, ['UPDATE TramParcel SET depot_id = NULL WHERE (invoice_id = ? AND line = ?)']],
    'a child keyed by a decimal its key does not print as the column holds it' => [function (): array {
        $tariff = new TramTariff('1.50', 'z1', label: 'Peak');
        $tariff->depot = new TramDepot('Holloway');

        $depot = $tariff->depot;
        $this->seed($depot, $tariff);

        return [$depot, 'tariffs', $tariff, 'depot'];
    }, ['UPDATE TramTariff SET depot_id = NULL WHERE (rate = ? AND zone = ?)']],
]);

describe('a selection longer than one statement binds', function (): void {
    beforeEach(function (): void {
        $this->kew = new Garden('Kew');
        $this->seedlings = array_map(function (int $index): Seedling {
            $seedling = new Seedling("Seedling {$index}");
            $seedling->garden = $this->kew;

            return $seedling;
        }, range(1, 2001));
        $this->seed($this->kew, ...$this->seedlings);

        $this->dissociateAll = fn (): Testable => $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $this->kew, 'seedlings', variants: ['noFetch']))
            ->selectTableRecords($this->seedlings)
            ->callAction(TestAction::make('dissociate')->table()->bulk());
        $this->statement = static fn (int $keys): string => 'UPDATE Seedling SET garden_id = NULL WHERE id IN (' . implode(', ', array_fill(0, $keys, '?')) . ')';
        $this->owned = fn (): int => $this->entityManager()->getRepository(Seedling::class)->count(['garden' => $this->kew->id]);
    });

    it('nulls the keys 2,000 a statement inside one transaction, toasting their sum', function (): void {
        ($this->dissociateAll)()->assertNotified('Dissociated');

        expect(($this->writes)())->toBe(['"START TRANSACTION"', ($this->statement)(2000), ($this->statement)(1), '"COMMIT"'])
            ->and(($this->owned)())->toBe(0);
    });

    it('dissociates nothing when the second statement fails, toasting that the whole selection failed', function (): void {
        FailingWrite::afterUpdates($this->entityManager(), Seedling::class, 2000);

        ($this->dissociateAll)()->assertNotified('Failed to dissociate 2001 records');

        expect(($this->writes)())->toBe(['"START TRANSACTION"', ($this->statement)(2000), ($this->statement)(1), '"ROLLBACK"'])
            ->and(($this->owned)())->toBe(2001);
    });
});

it("evicts what it nulls by key from the second-level cache, so the owner's cached children are read afresh", function (Closure $scene, string $property): void {
    [$owner, $lanterns] = $scene();
    $this->seed($owner, ...$lanterns);
    $ownerId = $this->entityManager()->getClassMetadata($owner::class)->getIdentifierValues($owner);

    $warm = $this->entityManager();
    count($warm->find($owner::class, $ownerId)?->lanterns ?? []);
    $warmed = [$warm->getCache()?->containsCollection($owner::class, 'lanterns', $ownerId), $warm->getCache()?->containsEntity(CachedLantern::class, $lanterns[0]->id)];
    $warm->clear();

    // One kernel serves every request, so the cache warmed here is the one the action writes past.
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $this->createLiveComponent(AssociableRelationTable::class, AssociableRelationTable::props($this->entityManager(), $owner, 'lanterns', variants: ['noFetch']), client: $client)
        ->selectTableRecords([$lanterns[0]])
        ->callAction(TestAction::make('dissociate')->table()->bulk())
        ->assertNotified('Dissociated');

    $read = $this->entityManager();
    $read->clear();
    [$dissociated] = array_map(static fn (CachedLantern $lantern): ?CachedLantern => $read->find(CachedLantern::class, $lantern->id), $lanterns);

    expect($warmed)->toBe([true, true])
        ->and($dissociated?->{$property})->toBeNull()
        ->and(array_map(static fn (CachedLantern $lantern): string => $lantern->name, $read->find($owner::class, $ownerId)?->lanterns->toArray() ?? []))->toEqualCanonicalizing(['Beta', 'Gamma']);
})->with([
    'over a foreign key of one column' => [static function (): array {
        $shelf = new LanternShelf('North');

        return [$shelf, array_map(static function (string $name) use ($shelf): CachedLantern {
            $lantern = new CachedLantern($name);
            $lantern->shelf = $shelf;

            return $lantern;
        }, ['Alpha', 'Beta', 'Gamma'])];
    }, 'shelf'],
    'over a foreign key of two columns' => [static function (): array {
        $stand = new LanternStand(aisle: 3, bay: 1);

        return [$stand, array_map(static function (string $name) use ($stand): CachedLantern {
            $lantern = new CachedLantern($name);
            $lantern->stand = $stand;

            return $lantern;
        }, ['Alpha', 'Beta', 'Gamma'])];
    }, 'stand'],
]);
