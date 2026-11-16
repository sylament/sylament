<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Tests\Fixtures\Entity\Berth;
use Sylament\Tests\Fixtures\Entity\BerthCleat;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Entity\LighthouseBeam;
use Sylament\Tests\Fixtures\Entity\LighthouseLog;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Wharf;
use Sylament\Tests\Fixtures\Entity\WharfCrane;
use Sylament\Tests\Fixtures\Schemas\Selectors;

beforeEach(function (): void {
    $this->host = $this->validatingRecordMappingHost();
});

describe('relationship()', function (): void {
    it('nests its fields under the relation name and fills them off the related record', function (): void {
        $lighthouse = new Lighthouse();
        $lighthouse->name = 'Fastnet';
        $lighthouse->setLog(new LighthouseLog());
        $lighthouse->getLog()->keeper = 'Ada';
        $this->seed($lighthouse);
        $lighthouse = $this->entityManager()->find(Lighthouse::class, $lighthouse->id);

        $this->host->mapping(
            TextInput::make('name'),
            Group::make([TextInput::make('keeper')])->relationship('log'),
        )->record($lighthouse)->fill($lighthouse);

        expect($this->host->data)->toBe(['name' => 'Fastnet', 'log' => ['keeper' => 'Ada']]);
    });

    it('fills its fields empty where the record holds no related record', function (): void {
        $lighthouse = new Lighthouse();
        $this->host->mapping(Group::make([TextInput::make('keeper')])->relationship('log'))->record($lighthouse)->fill($lighthouse);

        expect($this->host->data)->toBe(['log' => ['keeper' => null]]);
    });

    it('refuses a declaration that names no association', function (?string $name): void {
        expect(fn (): Group => Group::make()->relationship($name))->toThrow(
            LogicException::class,
            Group::class . ' declares relationship(), but names no association property to entangle with — a layout component carries no name of its own to fall back on. Pass the property Doctrine maps as relationship()\'s first argument.',
        );
    })->with([
        'no name' => [null],
        'a blank name' => [''],
    ]);

    it('refuses a to-many association, pointing at the Repeater', function (): void {
        $lighthouse = new Lighthouse();
        $schema = $this->host->mapping(Group::make([TextInput::make('name')])->relationship('spares'))->record($lighthouse);

        expect(fn (): mixed => $schema->fill($lighthouse))->toThrow(
            LogicException::class,
            Group::class . ' declares relationship("spares"), but that association holds a collection of ' . Product::class . ' rather than a single record, so one set of fields could not map it. Use a Repeater for a to-many association, or drop relationship() and map the related record by hand after getData().',
        );
    });

    it('refuses two components entangling one relationship', function (): void {
        $lighthouse = new Lighthouse();
        $schema = $this->host->mapping(
            Group::make([TextInput::make('keeper')])->relationship('log'),
            Group::make([TextInput::make('keeper')])->relationship('log'),
        )->record($lighthouse);

        expect(fn (): mixed => $schema->fill($lighthouse))->toThrow(LogicException::class, Group::class . ' and ' . Group::class . ' both map record path "log", which one of them maps wholesale');
    });
});

describe('the leaves of the related record', function (): void {
    beforeEach(function (): void {
        $log = new LighthouseLog();
        $log->keeper = 'Ada';
        $log->openedOn = new DateTimeImmutable('2026-03-14');
        $log->beam = LighthouseBeam::Fixed;
        $log->supplier = new Product('Lens');

        $this->lighthouse = new Lighthouse();
        $this->lighthouse->setLog($log);
        $this->seed($log->supplier, $this->lighthouse);
        $this->lighthouse = $this->entityManager()->find(Lighthouse::class, $this->lighthouse->id);
    });

    it("fills a date and an enum its fields' casts flatten", function (): void {
        $this->host->mapping(Group::make([
            TextInput::make('openedOn')->stateCast(new DateTimeStateCast('Y-m-d', 'Y-m-d', date_default_timezone_get(), DateTimeImmutable::class)),
            TextInput::make('beam')->stateCast(new EnumStateCast(LighthouseBeam::class)),
        ])->relationship('log'))->record($this->lighthouse)->fill($this->lighthouse);

        expect($this->host->data)->toBe(['log' => ['openedOn' => '2026-03-14', 'beam' => 'fixed']]);
    });

    it('fills an association, an object no cast flattens and a leaf the related record lacks empty', function (): void {
        $this->host->mapping(Group::make([TextInput::make('openedOn'), TextInput::make('supplier'), TextInput::make('nickname')])->relationship('log'))
            ->record($this->lighthouse)
            ->fill($this->lighthouse);

        expect($this->host->data)->toBe(['log' => ['openedOn' => null, 'supplier' => null, 'nickname' => null]]);
    });

    it('saves onto the related record in place, writing only the leaves the submission carries', function (): void {
        $log = $this->lighthouse->getLog();
        $schema = $this->host->mapping(Group::make([
            TextInput::make('keeper')->dehydrated(false),
            TextInput::make('openedOn')->stateCast(new DateTimeStateCast('Y-m-d', 'Y-m-d', date_default_timezone_get(), DateTimeImmutable::class)),
            TextInput::make('beam')->stateCast(new EnumStateCast(LighthouseBeam::class)),
        ])->relationship('log'))->record($this->lighthouse);
        $schema->fill($this->lighthouse);
        $this->host->data['log']['keeper'] = 'Grace';
        $this->host->data['log']['beam'] = 'flashing';

        expect($schema->getData()->getLog())->toBe($log);

        $this->entityManager()->flush();

        expect($this->reload($log))
            ->keeper->toBe('Ada')
            ->openedOn->toEqual(new DateTimeImmutable('2026-03-14'))
            ->beam->toBe(LighthouseBeam::Flashing);
    });

    it("writes a nested container's leaves, never the container's own path", function (): void {
        $this->lighthouse->name = 'Fastnet';
        $schema = $this->host->mapping(Group::make([
            TextInput::make('keeper'),
            Section::make()->statePath('lighthouse')->components([TextInput::make('name')]),
        ])->relationship('log'))->record($this->lighthouse);
        $schema->fill($this->lighthouse);
        $this->host->data['log']['keeper'] = 'Grace';
        $this->host->data['log']['lighthouse']['name'] = 'Skerryvore';

        $schema->getData();

        expect($this->lighthouse)
            ->name->toBe('Skerryvore')
            ->getLog()->keeper->toBe('Grace');
    });
});

describe('a record that holds no related record', function (): void {
    it('builds one through the setter and leaves its insert to the cascade', function (): void {
        $lighthouse = new Lighthouse();
        $this->seed($lighthouse);
        $lighthouse = $this->entityManager()->find(Lighthouse::class, $lighthouse->id);
        $schema = $this->host->mapping(Group::make([TextInput::make('keeper')])->relationship('log'))->record($lighthouse);
        $schema->fill($lighthouse);
        $this->host->data['log']['keeper'] = 'Grace';

        $log = $schema->getData()->getLog();
        $this->entityManager()->flush();
        $this->entityManager()->clear();

        expect($log)->toBeInstanceOf(LighthouseLog::class)
            ->lighthouse->toBe($lighthouse)
            ->and($this->entityManager()->find(LighthouseLog::class, $log->id))
            ->keeper->toBe('Grace')
            ->lighthouse->id->toBe($lighthouse->id);
    });

    it('refuses by name a related record whose class needs constructor arguments', function (): void {
        $schema = $this->host->mapping(Group::make([TextInput::make('id')])->relationship('invoice'))->record(new Lighthouse());
        $this->host->data = ['invoice' => ['id' => 7]];

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            'Cannot map state path "data.invoice" onto a new related record: ' . Invoice::class . ' requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or drop relationship() and create the related record by hand after getData().',
        );
    });
});

describe('the key of a related record it builds', function (): void {
    it('refuses by name one whose key is still incomplete once the setter ran', function (string $relationship, array $fields, array $state, string $class): void {
        $schema = $this->host->mapping(Group::make(array_map(TextInput::make(...), $fields))->relationship($relationship))->record(new Berth());
        $this->host->data = [$relationship => $state];

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            sprintf('The [%s] relationship is saving a new [%s] whose identifier is incomplete, so no record key can be minted for it. Give every identifier field a component — enabled, dehydrated and required — inside the relationship, or on the record it hangs off when its identifier is that record, or drop relationship() and create the related record by hand after getData().', $relationship, $class),
        );
    })->with([
        'two strings, one of them blank' => ['mooring', ['pier', 'slot', 'label'], ['pier' => 'North', 'slot' => '   ', 'label' => 'Spaced'], BerthMooring::class],
        'a string no field maps' => ['mooring', ['pier', 'label'], ['pier' => 'North', 'label' => 'Half'], BerthMooring::class],
        'the owner and a blank string' => ['cleat', ['slot', 'label'], ['slot' => '   ', 'label' => 'Spaced'], BerthCleat::class],
    ]);

    it('saves one whose key the fields and the setter fill', function (string $relationship, array $fields, array $state, Closure $key): void {
        $berth = new Berth();
        $schema = $this->host->mapping(Group::make(array_map(TextInput::make(...), $fields))->relationship($relationship))->record($berth);
        $this->host->data = [$relationship => $state];

        $schema->getData();
        $this->seed($berth);

        expect($this->entityManager()->find($relationship === 'mooring' ? BerthMooring::class : BerthCleat::class, $key($berth)))->label->toBe('Moored');
    })->with([
        'two strings' => ['mooring', ['pier', 'slot', 'label'], ['pier' => 'North', 'slot' => 'S1', 'label' => 'Moored'], fn (Berth $berth): array => ['pier' => 'North', 'slot' => 'S1']],
        'the owner and a string' => ['cleat', ['slot', 'label'], ['slot' => 'S1', 'label' => 'Moored'], fn (Berth $berth): array => ['berth' => $berth->id, 'slot' => 'S1']],
    ]);

    it('leaves a blank single string key to the application', function (): void {
        $berth = new Berth();
        $schema = $this->host->mapping(Group::make([TextInput::make('code'), TextInput::make('label')])->relationship('buoy'))->record($berth);
        $this->host->data = ['buoy' => ['code' => '   ', 'label' => 'Spaced']];

        expect($schema->getData()->buoy)->code->toBe('   ');
    });

    it('asks nothing of the key of a related record already there', function (): void {
        $berth = new Berth();
        $berth->mooring = new BerthMooring();
        $berth->mooring->pier = 'North';
        $berth->mooring->slot = '   ';
        $this->seed($berth);
        $berth = $this->entityManager()->find(Berth::class, $berth->id);
        $schema = $this->host->mapping(Group::make([TextInput::make('label')])->relationship('mooring'))->record($berth);
        $schema->fill($berth);
        $this->host->data['mooring']['label'] = 'Relabelled';

        expect($schema->getData()->mooring)->label->toBe('Relabelled');
    });

    it("refuses one keyed by its owner while the owner's own optional key is blank, sending the advice to the owner too", function (): void {
        $schema = $this->host->mapping(
            TextInput::make('code'),
            Group::make([TextInput::make('label')])->relationship('crane'),
        )->record(new Wharf());
        $this->host->data = ['code' => '   ', 'crane' => ['label' => 'Lifted']];

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            'The [crane] relationship is saving a new [' . WharfCrane::class . '] whose identifier is incomplete, so no record key can be minted for it. Give every identifier field a component — enabled, dehydrated and required — inside the relationship, or on the record it hangs off when its identifier is that record, or drop relationship() and create the related record by hand after getData().',
        );
    });
});

describe('a relationship switched off', function (): void {
    it('still fills, then nulls the property and deletes the related record, running no hook beneath it, though the mapping removes no orphan', function (): void {
        $lighthouse = new Lighthouse();
        $lighthouse->setLog(new LighthouseLog());
        $lighthouse->getLog()->keeper = 'Ada';
        $this->seed($lighthouse);
        $lighthouse = $this->entityManager()->find(Lighthouse::class, $lighthouse->id);
        $logId = $lighthouse->getLog()->id;
        $hooked = 0;
        $schema = $this->host->mapping(Group::make([
            TextInput::make('keeper'),
            TextInput::make('openedOn')->saveRelationshipsUsing(function () use (&$hooked): void {
                $hooked++;
            }),
        ])->relationship('log', condition: false))->record($lighthouse);
        $schema->fill($lighthouse);

        expect($this->host->data['log']['keeper'])->toBe('Ada');

        $this->host->data['log']['keeper'] = 'Grace';

        expect($schema->getData()->getLog())->toBeNull();

        $this->entityManager()->flush();
        $this->entityManager()->clear();

        expect($this->entityManager()->find(LighthouseLog::class, $logId))->toBeNull()
            ->and($this->entityManager()->find(Lighthouse::class, $lighthouse->id))->toBeInstanceOf(Lighthouse::class)
            ->and($hooked)->toBe(0);
    });

    it('refuses by name a property that does not accept null', function (): void {
        $lighthouse = new Lighthouse();
        $lighthouse->lamp = new Product('Lens');

        $schema = $this->host->mapping(Group::make([TextInput::make('name')])->relationship('lamp', condition: false))->record($lighthouse);
        $schema->fill($lighthouse);

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            'Cannot switch off relationship("lamp") on ' . Group::class . ': ' . Lighthouse::class . ' declares that property non-nullable, so the related record could not be detached before it is deleted. Make the property nullable, or drop the relationship() condition.',
        );
    });
});

describe('the subtree', function (): void {
    it('hands every closure inside it the related record, or its class where there is none, never the outer record', function (bool $logged, string $expected): void {
        $lighthouse = new Lighthouse();

        if ($logged) {
            $lighthouse->setLog(new LighthouseLog());
        }

        $seen = [];
        $this->host->mapping(Group::make([
            TextInput::make('keeper')->afterStateHydrated(function (mixed $record, Component $component) use (&$seen): void {
                $seen = [get_debug_type($record), $component->getRecordClass()];
            }),
        ])->relationship('log'))->record($lighthouse)->fill($lighthouse);

        expect($seen)->toBe([$expected, LighthouseLog::class]);
    })->with([
        'a related record' => [true, LighthouseLog::class],
        'none yet' => [false, 'null'],
    ]);

    it("re-roots every child schema of a section, decoration ones included, under the section's own configuration", function (): void {
        $lighthouse = new Lighthouse();
        $lighthouse->setLog(new LighthouseLog());

        $seen = null;
        $section = Section::make()->relationship('log')
            ->components([TextInput::make('keeper')])
            ->belowContent([TextInput::make('openedOn')->afterStateHydrated(function (mixed $record) use (&$seen): void {
                $seen = $record;
            })]);
        $this->host->mapping($section)->record($lighthouse)->fill($lighthouse);

        $crawler = $this->renderSchemaComponent(Section::make()->relationship('log')
            ->components([TextInput::make('keeper')])
            ->belowContent([TextInput::make('openedOn')]));

        expect($seen)->toBe($lighthouse->getLog())
            ->and($crawler->filter(Selectors::INLINE . ' [name="openedOn"]')->count())->toBe(1)
            ->and($crawler->filter(Selectors::INLINE . ' [name="keeper"]')->count())->toBe(0);
    });

    it('fills a hooked path from its hook, from any child schema, and leaves a path hooked only on the other edge to the leaf walk', function (): void {
        $lighthouse = new Lighthouse();
        $lighthouse->setLog(new LighthouseLog());
        $lighthouse->getLog()->keeper = 'Ada';
        $lighthouse->getLog()->beam = LighthouseBeam::Fixed;
        $schema = $this->host->mapping(Section::make()->relationship('log')
            ->components([
                TextInput::make('keeper')->loadStateFromRelationshipsUsing(fn (LighthouseLog $record): string => 'Keeper ' . $record->keeper),
                TextInput::make('beam')->stateCast(new EnumStateCast(LighthouseBeam::class))->saveRelationshipsUsing(fn (): null => null),
            ])
            ->belowContent([TextInput::make('lighthouse')->loadStateFromRelationshipsUsing(fn (LighthouseLog $record): string => get_debug_type($record->lighthouse))]))
            ->record($lighthouse);
        $schema->fill($lighthouse);

        expect($this->host->data)->toBe(['log' => ['keeper' => 'Keeper Ada', 'lighthouse' => Lighthouse::class, 'beam' => 'fixed']]);

        $this->host->data['log'] = ['keeper' => 'Grace'];
        $schema->getData();

        expect($lighthouse->getLog()->keeper)->toBe('Grace');
    });

    it('hands a hooked path its submission after the leaves are written and before a new record is attached, only where the submission carries it', function (): void {
        $lighthouse = new Lighthouse();
        $this->seed($lighthouse);
        $lighthouse = $this->entityManager()->find(Lighthouse::class, $lighthouse->id);
        $seen = [];
        $hidden = 0;
        $schema = $this->host->mapping(Group::make([
            TextInput::make('keeper')->saveRelationshipsUsing(function (LighthouseLog $record, string $state) use ($lighthouse, &$seen): void {
                $seen = [$record->beam, $lighthouse->getLog()];
                $record->keeper = mb_strtoupper($state);
            }),
            TextInput::make('beam')->stateCast(new EnumStateCast(LighthouseBeam::class)),
            TextInput::make('openedOn')->hidden()->saveRelationshipsUsing(function () use (&$hidden): void {
                $hidden++;
            }),
        ])->relationship('log'))->record($lighthouse);
        $schema->fill($lighthouse);
        $this->host->data['log'] = ['keeper' => 'Grace', 'beam' => 'fixed'];

        expect($schema->getData()->getLog()->keeper)->toBe('GRACE')
            ->and($seen)->toBe([LighthouseBeam::Fixed, null])
            ->and($hidden)->toBe(0);
    });
});
