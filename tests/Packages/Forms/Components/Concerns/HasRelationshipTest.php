<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Charter;
use Sylament\Tests\Fixtures\Entity\CharterSeal;
use Sylament\Tests\Fixtures\Entity\CharterSealClause;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Entity\SeedPacket;
use Sylament\Tests\Fixtures\Entity\SeedVault;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Symfony\Component\Uid\Uuid;

describe('relationship()', function (): void {
    it("lists every target row keyed by its identifier and labelled by the title attribute, and fills the keys the record holds, the field's own name naming the association", function (): void {
        $garden = new Garden('North');
        $garden->addPlant($fern = new Plant('Fern'));
        $this->seed($fern, $moss = new Plant('Moss'), $garden);
        $garden = $this->entityManager()->find(Garden::class, $garden->id);

        $host = new RecordMappingHost();
        $schema = $host->mapping(CheckboxList::make('plants')->relationship(titleAttribute: 'name'))->record($garden);
        [$list] = $schema->getComponents();
        $schema->fill($garden);

        expect($list->getOptions())->toBe([$fern->id => 'Fern', $moss->id => 'Moss'])
            ->and($host->data)->toBe(['plants' => [(string) $fern->id]]);
    });

    it('fills a to-one field with the key the record holds, and writes the selection back as a reference to the row validation read, a blank one as null', function (string $field, Closure $select, Closure $targets): void {
        $entities = $targets();
        [$held, $picked] = $entities;
        $seedling = new Seedling('Sprout');
        $seedling->{$field} = $held;
        $this->seed($seedling, ...$entities);
        $seedling = $this->entityManager()->find(Seedling::class, $seedling->id);
        $reader = $this::getContainer()->get(AssociationReader::class);

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping($select())->record($seedling);
        $schema->fill($seedling);

        expect($host->data)->toBe([$field => $reader->getRecordKey($held)]);

        $host->data[$field] = $reader->getRecordKey($picked);
        $read = count($this->statementsRun());
        $written = $schema->getData()->{$field};

        expect(array_slice($this->statementsRun(), $read))->toHaveCount(1)
            ->and($written)->toBeInstanceOf($picked::class)
            ->and($reader->getRecordKey($written))->toBe($reader->getRecordKey($picked));

        $host->data[$field] = null;

        expect($schema->getData()->{$field})->toBeNull();
    })->with([
        'a single generated id' => ['garden', fn (): Select => Select::make('garden')->relationship('garden', 'name'), fn (): array => [new Garden('Fern'), new Garden('Moss')]],
        'two scalars' => ['terrace', fn (): Select => Select::make('terrace')->relationship('terrace', 'number'), fn (): array => [new Terrace(1, 1), new Terrace(1, 2)]],
        'an association plus a scalar' => ['allotment', fn (): Select => Select::make('allotment')->relationship('allotment', 'number'), function (): array {
            $garden = new Garden('Plot');

            return [new Allotment($garden, 1), new Allotment($garden, 2)];
        }],
        'an association alone' => ['vent', fn (): Select => Select::make('vent')->relationship('vent')->getOptionLabelFromRecordUsing(static fn (GreenhouseVent $vent): string => $vent->greenhouse->id->toRfc4122()), function (): array {
            $held = new Greenhouse(Uuid::v7());
            $picked = new Greenhouse(Uuid::v7());

            return [new GreenhouseVent($held), new GreenhouseVent($picked), $held, $picked];
        }],
    ]);

    it('skips a blank member of a list handed to getData(), and refuses one that is no identifier by name', function (): void {
        $this->seed($garden = new Garden('North'), $fern = new Plant('Fern'));
        $garden = $this->entityManager()->find(Garden::class, $garden->id);
        $schema = new RecordMappingHost()->mapping(CheckboxList::make('plants')->relationship(titleAttribute: 'name'))->record($garden);

        expect($schema->getData(['plants' => ['', (string) $fern->id]])->plants->map(static fn (Plant $plant): string => $plant->name)->getValues())->toBe(['Fern']);

        expect(fn (): object => $schema->getData(['plants' => [['fern']]]))->toThrow(LogicException::class, 'The [plants] field submitted array where the [plants] relationship expected an entity identifier.');
    });

    it('scopes the option map through modifyQueryUsing, whether it answers the builder or nothing, and refuses any other answer by name', function (): void {
        $this->seed($fern = new Plant('Fern'), new Plant('Moss'));
        $options = static function (Closure $scope): array {
            [$list] = new RecordMappingHost()->mapping(CheckboxList::make('plants')->relationship(titleAttribute: 'name', modifyQueryUsing: $scope))->record(Garden::class)->getComponents();

            return $list->getOptions();
        };

        expect($options(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name <> 'Moss'")))->toBe([$fern->id => 'Fern'])
            ->and($options(static function (QueryBuilder $query): void {
                $query->andWhere("record.name <> 'Moss'");
            }))->toBe([$fern->id => 'Fern'])
            ->and(fn (): array => $options(static fn (): string => 'record.name'))->toThrow(LogicException::class, "The [plants] field's relationship modifyQueryUsing returned string. Return the QueryBuilder it was handed, or nothing at all — anything else would drop the scoping without a trace.");
    });

    it('refuses a relationship with neither a title attribute nor a label callback, naming the call', function (): void {
        [$list] = new RecordMappingHost()->mapping(CheckboxList::make('plants')->relationship())->record(Garden::class)->getComponents();
        $this->seed(new Plant('Fern'));

        expect(fn (): array => $list->getOptions())->toThrow(LogicException::class, 'The [plants] field declares relationship("plants") without a titleAttribute, so its options have nothing to be labelled by. Pass the property that labels the target entity as relationship()\'s second argument.');
    });

    it('refuses a field whose multiple() contradicts the association, declared on either side of relationship()', function (Closure $select, string $recordClass, string $message): void {
        $record = new $recordClass();
        $schema = new RecordMappingHost()->mapping($select())->record($record);

        expect(fn (): mixed => $schema->fill($record))->toThrow(LogicException::class, $message);
    })->with([
        'a to-many association without multiple()' => [
            fn (): Select => Select::make('plants')->relationship('plants', 'name'),
            Garden::class,
            'The [plants] field declares relationship("plants"), but that association holds a collection of ' . Plant::class . ' while the field speaks a single value — every selection but one would be dropped. Call multiple() on this field, or point relationship() at a to-one association.',
        ],
        'a to-one association with multiple() declared first' => [
            fn (): Select => Select::make('garden')->multiple()->relationship('garden', 'name'),
            Seedling::class,
            'The [garden] field declares relationship("garden") alongside multiple(), but that association holds a single ' . Garden::class . ' rather than a collection, so a list of selections could not map it. Drop multiple(), or point relationship() at a to-many association.',
        ],
        'a to-one association with multiple() declared after' => [
            fn (): Select => Select::make('garden')->relationship('garden', 'name')->multiple(),
            Seedling::class,
            'The [garden] field declares relationship("garden") alongside multiple(), but that association holds a single ' . Garden::class . ' rather than a collection, so a list of selections could not map it. Drop multiple(), or point relationship() at a to-many association.',
        ],
    ]);
});

describe('the three closures', function (): void {
    it('hands each composing class to the closures as self, so the option map, the fill and the save run on either field', function (Closure $field): void {
        $garden = new Garden('North');
        $garden->addPlant($fern = new Plant('Fern'));
        $this->seed($fern, $moss = new Plant('Moss'), $garden);
        $garden = $this->entityManager()->find(Garden::class, $garden->id);

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping($field())->record($garden);
        [$component] = $schema->getComponents();
        $schema->fill($garden);

        expect($component->getOptions())->toBe([$fern->id => 'Fern', $moss->id => 'Moss'])
            ->and($host->data)->toBe(['plants' => [(string) $fern->id]]);

        $host->data['plants'] = [(string) $moss->id];

        expect($schema->getData()->plants->map(static fn (Plant $plant): string => $plant->name)->getValues())->toBe(['Moss']);
    })->with([
        'CheckboxList' => [fn (): CheckboxList => CheckboxList::make('plants')->relationship(titleAttribute: 'name')],
        'Select' => [fn (): Select => Select::make('plants')->relationship(titleAttribute: 'name')->multiple()->preload()],
    ]);

    it('keys the option map by record key: a single number as an int, a composite key by its ~ form, and a single derived identity as its one field', function (string $recordClass, string $field, string $titleAttribute, Closure $targets, Closure $keys): void {
        $entities = $targets();
        $this->seed(...$entities);

        [$select] = new RecordMappingHost()->mapping(Select::make($field)->relationship($field, $titleAttribute))->record($recordClass)->getComponents();

        expect(array_keys($select->getOptions()))->toBe($keys($entities));
    })->with([
        'a single generated id' => [Seedling::class, 'garden', 'name', fn (): array => [new Garden('Fern')], fn (array $entities): array => [$entities[0]->id]],
        'two scalars' => [Seedling::class, 'terrace', 'number', fn (): array => [new Terrace(1, 2)], fn (): array => ['1~2']],
        'an association plus a scalar' => [Seedling::class, 'allotment', 'number', fn (): array => [new Allotment(new Garden('Plot'), 3)], fn (array $entities): array => ["{$entities[0]->garden->id}~3"]],
        'a single identity derived from a numbered record' => [CharterSealClause::class, 'seal', 'note', function (): array {
            $charter = new Charter(5);

            return [$charter, new CharterSeal($charter, 'Wax')];
        }, fn (): array => [5]],
    ]);

    it("labels a clone's options by the clone's own configuration, not the field it was cloned from", function (): void {
        $this->seed($fern = new Plant('Fern'));
        [$list] = new RecordMappingHost()->mapping(CheckboxList::make('plants')->relationship(titleAttribute: 'name'))->record(Garden::class)->getComponents();

        $clone = $list->getClone()->getOptionLabelFromRecordUsing(static fn (Plant $record): string => mb_strtoupper($record->name));

        expect($clone->getOptions())->toBe([$fern->id => 'FERN'])
            ->and($list->getOptions())->toBe([$fern->id => 'Fern']);
    });

    it('memoizes the option map until relationship(), the label callback or forgetCachedRelationshipOptions() resets it, and re-queries every read under a query scope', function (): void {
        $this->seed($fern = new Plant('Fern'));
        [$list, $scoped] = new RecordMappingHost()->mapping(
            CheckboxList::make('plants')->relationship(titleAttribute: 'name'),
            CheckboxList::make('scoped')->relationship('plants', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query),
        )->record(Garden::class)->getComponents();

        $list->getOptions();
        $list->getOptions();

        $scoped->getOptions();
        $scoped->getOptions();

        expect($this->statementsRun())->toHaveCount(3);

        $this->seed($moss = new Plant('Moss'));

        expect($list->getOptions())->toBe([$fern->id => 'Fern'])
            ->and($this->statementsRun())->toBe([]);

        $list->forgetCachedRelationshipOptions();

        expect($list->getOptions())->toBe([$fern->id => 'Fern', $moss->id => 'Moss']);

        $this->seed($ivy = new Plant('Ivy'));

        expect($list->relationship(titleAttribute: 'name')->getOptions())->toBe([$fern->id => 'Fern', $moss->id => 'Moss', $ivy->id => 'Ivy'])
            ->and($list->getOptionLabelFromRecordUsing(static fn (Plant $record): string => "{$record->name} plant")->getOptions())->toBe([$fern->id => 'Fern plant', $moss->id => 'Moss plant', $ivy->id => 'Ivy plant']);
    });
});

describe('refusals', function (): void {
    it('refuses a relationship whose options it cannot enumerate, naming what is missing, rather than answering an empty map', function (bool $withoutManager, ?string $recordClass, string $field, string $reason): void {
        $schema = new RecordMappingHost()->mapping(CheckboxList::make($field)->relationship(titleAttribute: 'name'));

        if ($recordClass !== null) {
            $schema->record($recordClass);
        }

        [$list] = $schema->getComponents();

        if ($withoutManager) {
            $list->setAssociationReader(new AssociationReader());
        }

        expect(fn (): array => $list->getOptions())->toThrow(LogicException::class, "The [{$field}] field declares relationship(\"{$field}\"), but {$reason} or replace relationship() with an options() map.");
    })->with([
        'no entity manager' => [true, Garden::class, 'plants', 'no Doctrine EntityManager backs it, so its options could only ever come back empty — and an empty option list silently rejects every submitted value. Register Doctrine (doctrine/doctrine-bundle),'],
        'no record class' => [false, null, 'plants', 'the schema names no record class to read the association off. Give the schema a record() instance or class-string,'],
        'no association at the property' => [false, Garden::class, 'name', '[' . Garden::class . '] maps no association at "name". Name the property Doctrine maps,'],
    ]);
});

describe('saving a to-many selection', function (): void {
    it('writes the selection onto the collection the record holds, flushing only the changed join rows and keeping the target it dropped', function (): void {
        $garden = new Garden('North');
        $garden->addPlant($fern = new Plant('Fern'));
        $garden->addPlant($moss = new Plant('Moss'));
        $this->seed($fern, $moss, $ivy = new Plant('Ivy'), $garden);
        $garden = $this->entityManager()->find(Garden::class, $garden->id);

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(CheckboxList::make('plants')->relationship(titleAttribute: 'name'))->record($garden);
        $schema->fill($garden);

        $collection = $garden->plants;
        $host->data['plants'] = [(string) $moss->id, (string) $ivy->id];

        expect($schema->getData()->plants)->toBe($collection);

        $this->entityManager()->flush();
        $writes = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_starts_with($sql, 'INSERT') || str_starts_with($sql, 'DELETE')));

        $this->entityManager()->clear();

        expect(array_map(static fn (string $sql): string => implode(' ', array_slice(explode(' ', $sql), 0, 3)), $writes))->toBe(['DELETE FROM garden_plant', 'INSERT INTO garden_plant'])
            ->and($this->reload($garden)->plants->map(static fn (Plant $plant): string => $plant->name)->getValues())->toEqualCanonicalizing(['Moss', 'Ivy'])
            ->and($this->reload($fern)->name)->toBe('Fern');
    });
});

describe('an inverse-side many-to-many', function (): void {
    it('writes an inverse-side selection through the owning side, leaving kept members alone, and re-fills the selection it wrote', function (array $picked, array $writes): void {
        $plant = new Plant('Fern');
        $gardens = ['North' => new Garden('North'), 'South' => new Garden('South'), 'East' => new Garden('East')];
        $gardens['North']->addPlant($plant);
        $gardens['South']->addPlant($plant);
        $plant->addGarden($gardens['North']);
        $plant->addGarden($gardens['South']);
        $this->seed($plant, ...array_values($gardens));
        $plant = $this->entityManager()->find(Plant::class, $plant->id);

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(CheckboxList::make('gardens')->relationship(titleAttribute: 'name'))->record($plant);
        $schema->fill($plant);
        $host->data['gardens'] = array_map(static fn (string $name): string => (string) $gardens[$name]->id, $picked);
        $schema->getData();
        $this->entityManager()->flush();
        $written = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_starts_with($sql, 'INSERT') || str_starts_with($sql, 'DELETE')));
        $schema->fill($plant);
        $refilled = $host->data['gardens'];
        $this->entityManager()->clear();

        expect(array_map(static fn (string $sql): string => implode(' ', array_slice(explode(' ', $sql), 0, 3)), $written))->toEqualCanonicalizing($writes)
            ->and($refilled)->toEqualCanonicalizing(array_map(static fn (string $name): string => (string) $gardens[$name]->id, $picked))
            ->and(array_map(fn (Garden $garden): array => $this->reload($garden)->plants->map(static fn (Plant $plant): string => $plant->name)->getValues(), $gardens))->toBe(array_map(static fn (string $name): array => in_array($name, $picked, true) ? ['Fern'] : [], ['North' => 'North', 'South' => 'South', 'East' => 'East']));
    })->with([
        'one member swapped for another' => [['South', 'East'], ['DELETE FROM garden_plant', 'INSERT INTO garden_plant']],
        'every member dropped' => [[], ['DELETE FROM garden_plant', 'DELETE FROM garden_plant']],
    ]);

    it('refuses a deselection the owning side would delete the saved record for, writing nothing, and saves a selection that only adds', function (): void {
        $north = new SeedVault('North');
        $north->addPacket($packet = new SeedPacket('Basil'));

        $packet->addVault($north);
        $this->seed($packet, $north, $south = new SeedVault('South'));
        $packet = $this->entityManager()->find(SeedPacket::class, $packet->id);
        $writes = fn (): array => array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => !str_starts_with($sql, 'SELECT')));

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(CheckboxList::make('vaults')->relationship(titleAttribute: 'name'))->record($packet);
        $schema->fill($packet);
        $host->data['vaults'] = [];

        expect(fn (): object => $schema->getData())->toThrow(LogicException::class, '[' . SeedVault::class . '::$packets] declares orphanRemoval, so a member dropped from it is deleted rather than unlinked — deselecting one in the [vaults] field would delete the record this form is saving rather than its join row. Put the field on the owning side of the association, or drop orphanRemoval.');

        $this->entityManager()->flush();

        expect($writes())->toBe([]);

        $host->data['vaults'] = [(string) $north->id, (string) $south->id];
        $schema->getData();
        $this->entityManager()->flush();

        $added = array_values(array_filter($writes(), static fn (string $sql): bool => str_starts_with($sql, 'INSERT') || str_starts_with($sql, 'DELETE')));
        $this->entityManager()->clear();

        expect(array_map(static fn (string $sql): string => implode(' ', array_slice(explode(' ', $sql), 0, 3)), $added))->toBe(['INSERT INTO seedvault_seedpacket'])
            ->and([$this->reload($north)->packets->count(), $this->reload($south)->packets->count()])->toBe([1, 1]);
    });
});

describe('a composite-keyed owner', function (): void {
    it('fills and saves the to-many of an owner keyed by two scalars or by an association plus a scalar, one join row for one added member', function (Closure $owner, string $joinTable): void {
        $owner = $owner();
        $owner->addPlant($fern = new Plant('Fern'));
        $this->seed($fern, $moss = new Plant('Moss'), $owner);
        $owner = $this->reload($owner);

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(CheckboxList::make('plants')->relationship(titleAttribute: 'name'))->record($owner);
        $schema->fill($owner);

        expect($host->data)->toBe(['plants' => [(string) $fern->id]]);

        $host->data['plants'] = [(string) $fern->id, (string) $moss->id];
        $schema->getData();
        $this->entityManager()->flush();
        $writes = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_starts_with($sql, 'INSERT') || str_starts_with($sql, 'DELETE')));

        $this->entityManager()->clear();

        expect(array_map(static fn (string $sql): string => implode(' ', array_slice(explode(' ', $sql), 0, 3)), $writes))->toBe(["INSERT INTO {$joinTable}"])
            ->and($this->reload($owner)->plants->map(static fn (Plant $plant): string => $plant->name)->getValues())->toEqualCanonicalizing(['Fern', 'Moss']);
    })->with([
        'two scalars' => [fn (): Terrace => new Terrace(1, 2), 'terrace_plant'],
        'an association plus a scalar' => [fn (): Allotment => new Allotment(new Garden('Plot'), 3), 'allotment_plant'],
    ]);
});
