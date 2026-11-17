<?php

declare(strict_types=1);

use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\CompanionHerb;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LineManifest;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\TariffBand;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\AttachableRelationTable;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\DomCrawler\Crawler;

describe('over a garden', function (): void {
    beforeEach(function (): void {
        $this->kew = new Garden('Kew');
        $this->kew->addPlant($this->rock = new Plant('Rock'));
        $this->kew->addPlant($this->moss = new Plant('Moss'));

        $this->wisley = new Garden('Wisley');
        $this->wisley->addPlant($this->basil = new Plant('Basil'));
        $this->seed($this->kew, $this->rock, $this->moss, $this->fern = new Plant('Fern'), $this->ivy = new Plant('Ivy'), $this->wisley, $this->basil);

        $this->table = fn (array $variants = [], ?string $titleAttribute = 'name'): Testable => $this->createLiveComponent(
            AttachableRelationTable::class,
            AttachableRelationTable::props($this->entityManager(), $this->kew, 'plants', $titleAttribute, $variants),
        );
        $this->mounted = fn (array $variants = [], ?string $titleAttribute = 'name'): Testable => ($this->table)($variants, $titleAttribute)->mountAction(TestAction::make('attach')->table());
        $this->plantsOfKew = function (): array {
            $names = array_map(static fn (Plant $plant): string => $plant->name, $this->reload($this->kew)->plants->toArray());
            sort($names);

            return $names;
        };
    });

    describe('the modal', function (): void {
        it('opens from a gray "Attach" header trigger onto an lg "Attach Plant" modal whose one field is a required record select, submitted by "Attach" or "Attach & attach another"', function (): void {
            $tester = ($this->table)();
            $trigger = $tester->render()->crawler()->filter(Selectors::trigger('attach') . SupportSelectors::GRAY);
            $modal = $tester->mountAction(TestAction::make('attach')->table())->render()->crawler();

            expect($trigger->text())->toBe('Attach')
                ->and($modal->filter(SupportSelectors::MODAL_HEADING)->text())->toBe('Attach Plant')
                ->and($modal->filter(SupportSelectors::OPEN_MODAL . ' ' . SupportSelectors::modalWidth('lg'))->count())->toBe(1)
                ->and($modal->filter(SupportSelectors::OPEN_MODAL . ' ' . FormsSelectors::FIELD_WRAPPER)->count())->toBe(1)
                ->and($modal->filter(Selectors::RECORD_SELECT_LABEL . SupportSelectors::SCREEN_READER_ONLY)->text())->toBe('Record')
                ->and($modal->filter(Selectors::RECORD_SELECT . '[aria-required="true"]')->count())->toBe(1)
                ->and($modal->filter(SupportSelectors::MODAL_SUBMIT)->text())->toBe('Attach')
                ->and($modal->filter(SupportSelectors::MODAL_FOOTER_ACTIONS)->each(static fn (Crawler $button): string => $button->text()))->toBe(['Attach', 'Attach & attach another', 'Cancel']);
        });

        it('offers no "Attach & attach another" under attachAnother(false)', function (): void {
            $modal = ($this->mounted)(['noAttachAnother'])->render()->crawler();

            expect($modal->filter(SupportSelectors::MODAL_FOOTER_ACTIONS)->each(static fn (Crawler $button): string => $button->text()))->toBe(['Attach', 'Cancel']);
        });

        it('refuses a submit that picks no record', function (): void {
            ($this->table)()
                ->callAction(TestAction::make('attach')->table())
                ->assertHasActionErrors(['recordId' => RequiredValue::class]);

            expect(($this->plantsOfKew)())->toBe(['Moss', 'Rock']);
        });
    });

    describe('the options', function (): void {
        it('lists nothing until the user types, then every plant the garden lacks whose title matches, in title order', function (): void {
            $tester = ($this->mounted)();

            expect($tester->render()->crawler()->filter(Selectors::RECORD_SELECT . ' ' . FormsSelectors::OPTION)->count())->toBe(0)
                ->and($this->recordSelectSearch($tester, ''))->toBe([(string) $this->basil->id => 'Basil', (string) $this->fern->id => 'Fern', (string) $this->ivy->id => 'Ivy'])
                ->and($this->recordSelectSearch($tester, 'er'))->toBe([(string) $this->fern->id => 'Fern']);
        });

        it('lists the candidates in the select as it opens once preloadRecordSelect() says so', function (): void {
            $modal = ($this->mounted)(['preloaded'])->render()->crawler();

            expect($modal->filter(Selectors::RECORD_SELECT . ' ' . FormsSelectors::OPTION)->each(static fn (Crawler $option): string => $option->text()))->toBe(['Basil', 'Fern', 'Ivy']);
        });

        it("caps the options at the select's optionsLimit, which a recordSelect() closure reshapes", function (): void {
            expect($this->recordSelectSearch(($this->mounted)(['limitedToTwo']), ''))->toBe([(string) $this->basil->id => 'Basil', (string) $this->fern->id => 'Fern']);
        });

        it('keeps the order a candidate query gives itself over the title order', function (): void {
            expect(array_values($this->recordSelectSearch(($this->mounted)(['selfOrdered']), '')))->toBe(['Basil', 'Ivy', 'Fern']);
        });

        it("titles the options through a table's recordTitle() closure, in title order, and reads an action's own title attribute without loading a candidate", function (): void {
            $hydrated = ($this->mounted)(['tableTitleClosure']);
            $titled = $this->recordSelectSearch($hydrated, '');
            $hydratedBasil = $this->entityManager()->getUnitOfWork()->tryGetById($this->basil->id, Plant::class);

            $projected = ($this->mounted)(['tableTitleClosure', 'actionTitleAttribute']);

            expect(array_values($titled))->toBe(['BASIL', 'FERN', 'IVY'])
                ->and($hydratedBasil)->toBeInstanceOf(Plant::class)
                ->and(array_values($this->recordSelectSearch($projected, '')))->toBe(['Basil', 'Fern', 'Ivy'])
                ->and($this->entityManager()->getUnitOfWork()->tryGetById($this->basil->id, Plant::class))->toBeFalse();
        });

        it('matches everything the candidate query admits when neither a search column nor a title attribute names a field', function (): void {
            expect(array_keys($this->recordSelectSearch(($this->mounted)(titleAttribute: null), 'zzz')))
                ->toEqualCanonicalizing([$this->fern->id, $this->ivy->id, $this->basil->id]);
        });

        it('hands recordSelectOptionsQuery() the candidate query and the search, null outside a search, and keeps the builder a closure narrows in place', function (): void {
            $tester = ($this->mounted)(['scoped']);
            $searched = $this->recordSelectSearch($tester, 'i');
            $searchLog = $this::getContainer()->get(HookLog::class)->hooks;

            $tester->setActionData(['recordId' => (string) $this->basil->id])->callMountedAction();

            expect($searched)->toBe([(string) $this->ivy->id => 'Ivy'])
                ->and($searchLog)->toBe(['options query: i'])
                ->and(array_unique($this::getContainer()->get(HookLog::class)->hooks))->toBe(['options query: null']);

            $tester->assertHasActionErrors(['recordId']);

            expect(($this->plantsOfKew)())->toBe(['Moss', 'Rock']);
        });

        it('offers what the builder recordSelectOptionsQuery() returns instead of the candidate query', function (): void {
            expect($this->recordSelectSearch(($this->mounted)(['replaced']), ''))->toBe([(string) $this->rock->id => 'Rock']);
        });

        it('lowers both sides of the search on PostgreSQL alone by default, and on every platform once forceSearchCaseInsensitive() says so', function (array $variants, bool $forced): void {
            $this->recordSelectSearch(($this->mounted)($variants), 'fern');
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
        it('makes the picked plant one of the garden\'s in one flush, toasts "Attached" and closes the modal', function (): void {
            $tester = ($this->table)()->callAction(TestAction::make('attach')->table(), data: ['recordId' => (string) $this->basil->id]);
            $commits = array_count_values($this->statementsRun())['"COMMIT"'] ?? 0;

            $tester->assertNotified('Attached')
                ->assertActionNotMounted();

            expect($commits)->toBe(1)
                ->and(($this->plantsOfKew)())->toBe(['Basil', 'Moss', 'Rock']);
        });

        it('attaches every plant a multiple() select picks in one flush', function (): void {
            ($this->table)(['multiple'])->callAction(TestAction::make('attach')->table(), data: ['recordId' => [(string) $this->basil->id, (string) $this->ivy->id]]);

            expect(array_count_values($this->statementsRun())['"COMMIT"'] ?? 0)->toBe(1)
                ->and(($this->plantsOfKew)())->toBe(['Basil', 'Ivy', 'Moss', 'Rock']);
        });

        it('refuses a picked plant the garden already has with a validation error', function (): void {
            ($this->table)()
                ->callAction(TestAction::make('attach')->table(), data: ['recordId' => (string) $this->rock->id])
                ->assertHasActionErrors(['recordId']);

            expect(($this->plantsOfKew)())->toBe(['Moss', 'Rock']);
        });

        it('accepts a pick past the options limit by asking the candidate query, and refuses it once preloaded, whose options are the whole choice', function (array $variants, bool $refused): void {
            $tester = ($this->table)(['limitedToTwo', ...$variants])->callAction(TestAction::make('attach')->table(), data: ['recordId' => (string) $this->ivy->id]);

            if ($refused) {
                $tester->assertHasActionErrors(['recordId']);
            }

            expect(($this->plantsOfKew)())->toBe($refused ? ['Moss', 'Rock'] : ['Ivy', 'Moss', 'Rock']);
        })->with([
            'searched' => [[], false],
            'preloaded' => [['preloaded'], true],
        ]);

        it('hands using() the relationship and every picked record instead of attaching them, binding record() only to a single pick', function (array $picks, string $logged): void {
            ($this->table)(['multiple', 'recordingAttach'])->callAction(TestAction::make('attach')->table(), data: ['recordId' => $picks]);

            expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$logged])
                ->and(($this->plantsOfKew)())->toBe(['Moss', 'Rock']);
        })->with([
            'one pick' => [fn (): array => [(string) $this->basil->id], 'attach to plants: Basil, bound: Basil'],
            'two picks' => [fn (): array => [(string) $this->basil->id, (string) $this->fern->id], 'attach to plants: Basil, Fern, bound: none'],
        ]);

        it('attaches through "Attach & attach another", then empties the select and keeps the modal open', function (): void {
            ($this->mounted)()
                ->setActionData(['recordId' => (string) $this->basil->id])
                ->callMountedAction(['another' => true])
                ->assertNotified('Attached')
                ->assertActionMounted(TestAction::make('attach')->table())
                ->assertActionDataSet(['recordId' => null]);

            expect(($this->plantsOfKew)())->toBe(['Basil', 'Moss', 'Rock']);
        });
    });

    describe('a submission the select does not check', function (): void {
        it('refuses a key the candidate query does not offer, writing nothing', function (array $variants, string $key): void {
            expect(fn (): Testable => ($this->table)(['permissive', ...$variants])->callAction(TestAction::make('attach')->table(), data: ['recordId' => $key]))
                ->toThrow(LogicException::class, 'The action [attach] was asked to attach a record its select never offered')
                ->and(($this->plantsOfKew)())->toBe(['Moss', 'Rock']);
        })->with([
            'malformed' => [[], fn (): string => 'rock'],
            "outside recordSelectOptionsQuery()'s scope" => [['scoped'], fn (): string => (string) $this->basil->id],
            'already attached' => [[], fn (): string => (string) $this->rock->id],
        ]);

        it('refuses a submission naming no record', function (): void {
            expect(fn (): Testable => ($this->table)(['permissive'])->callAction(TestAction::make('attach')->table()))
                ->toThrow(LogicException::class, 'The action [attach] was submitted with no record to attach.')
                ->and(($this->plantsOfKew)())->toBe(['Moss', 'Rock']);
        });
    });
});

describe('over the shapes a relationship takes', function (): void {
    it('searches the fields recordSelectSearchColumns() names instead of the title, a string-keyed list alike', function (string $variant): void {
        $basil = new CompanionHerb('Basil', 'Ocimum');
        $this->seed($basil, new CompanionHerb('Mint', 'Mentha'), new CompanionHerb('Thyme', 'Thymus'));

        $tester = $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $basil, 'companions', variants: [$variant]))
            ->mountAction(TestAction::make('attach')->table());

        expect(array_values($this->recordSelectSearch($tester, 'Men')))->toBe(['Mint'])
            ->and($this->recordSelectSearch($tester, 'Mint'))->toBe([]);
    })->with(['searchLatin', 'searchKeyed']);

    it('refuses a search column the target does not map, naming the action, the column and the class', function (): void {
        $this->seed($basil = new CompanionHerb('Basil', 'Ocimum'));

        $tester = $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $basil, 'companions', variants: ['searchUnmapped']))
            ->mountAction(TestAction::make('attach')->table());

        expect(fn (): array => $this->recordSelectSearch($tester, 'red'))
            ->toThrow(LogicException::class, sprintf('The action [attach] searches [colour] on [%s], which maps no such field.', CompanionHerb::class));
    });

    it('offers and attaches only what the owner lacks, whatever its keys and however its side is mapped', function (Closure $scene, string $relation, string $titleAttribute): void {
        [$owner, $held, $offered, $others] = $scene();
        $this->seed($owner, ...array_filter([...$held, ...$offered, ...$others], static fn (object $record): bool => $record !== $owner));
        $keys = array_map(fn (object $record): string => RecordKey::of($this->entityManager(), $record), $offered);

        $tester = $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $owner, $relation, $titleAttribute))
            ->mountAction(TestAction::make('attach')->table());

        expect(array_keys($this->recordSelectSearch($tester, '')))->toEqualCanonicalizing($keys);

        $tester->setActionData(['recordId' => $keys[0]])->callMountedAction()->assertNotified('Attached');

        expect(count($this->reload($owner)->{$relation}))->toBe(count($held) + 1)
            ->and($this->reload($owner)->{$relation}->exists(fn (int $index, object $record): bool => RecordKey::of($this->entityManager(), $record) === $keys[0]))->toBeTrue();
    })->with([
        'a target keyed by scalar fields' => [function (): array {
            $manifest = new LineManifest();
            $manifest->addOrderLine($held = new OrderLine(order: 7, line: 2, note: 'Held'));

            return [$manifest, [$held], [new OrderLine(order: 7, line: 3, note: 'Offered'), new OrderLine(order: 8, line: 2, note: 'Also offered')], []];
        }, 'orderLines', 'note'],
        'a target keyed by an association plus a scalar' => [function (): array {
            $manifest = new LineManifest();
            $manifest->addInvoiceLine($held = new InvoiceLine($seven = new Invoice(7), line: 2, note: 'Held'));

            return [$manifest, [$held], [new InvoiceLine($seven, line: 3, note: 'Offered'), new InvoiceLine(new Invoice(8), line: 2, note: 'Also offered')], []];
        }, 'invoiceLines', 'note'],
        'an owner keyed by scalar fields, mapped one way' => [function (): array {
            $terrace = new Terrace(estate: 4, number: 1);
            $terrace->addPlant($held = new Plant('Held'));

            $other = new Terrace(estate: 4, number: 2);
            $other->addPlant($offered = new Plant('Offered'));

            return [$terrace, [$held], [$offered], [$other]];
        }, 'plants', 'name'],
        'an owner keyed by an association plus a scalar, mapped one way' => [function (): array {
            $allotment = new Allotment(new Garden('Kew'), number: 1);
            $allotment->addPlant($held = new Plant('Held'));

            $other = new Allotment($allotment->garden, number: 2);
            $other->addPlant($offered = new Plant('Offered'));

            return [$allotment, [$held], [$offered], [$other]];
        }, 'plants', 'name'],
        'an owner of its own class' => [function (): array {
            $basil = new CompanionHerb('Basil');
            $basil->addCompanion($held = new CompanionHerb('Held'));

            return [$basil, [$held], [new CompanionHerb('Offered'), $basil], []];
        }, 'companions', 'name'],
    ]);
});

describe('a composite submission longer than one statement holds', function (): void {
    it('attaches every key, 500 keys a statement', function (Closure $lines, string $relation): void {
        $records = $lines();
        $this->seed($manifest = new LineManifest(), ...$records);
        $keys = array_map(fn (object $line): string => RecordKey::of($this->entityManager(), $line), $records);

        $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $manifest, $relation, 'note', ['multiple']))
            ->callAction(TestAction::make('attach')->table(), data: ['recordId' => $keys])
            ->assertNotified('Attached');

        expect(max(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), $this->statementsRun())))->toBe(500 * 2 + 1)
            ->and($this->reload($manifest)->{$relation})->toHaveCount(501);
    })->with([
        'scalar fields' => [static fn (): array => array_map(static fn (int $line): OrderLine => new OrderLine(order: 1, line: $line), range(1, 501)), 'orderLines'],
        'an association plus a scalar' => [static function (): array {
            $invoice = new Invoice(1);

            return array_map(static fn (int $line): InvoiceLine => new InvoiceLine($invoice, line: $line), range(1, 501));
        }, 'invoiceLines'],
    ]);

    it('refuses two spellings of one key that land in different statements, as one statement refuses them', function (): void {
        $bands = array_map(static fn (int $zone): TariffBand => new TariffBand('1.50', "z{$zone}"), range(1, 500));
        $this->seed($manifest = new LineManifest(), ...$bands);
        $keys = array_map(fn (TariffBand $band): string => RecordKey::of($this->entityManager(), $band), $bands);

        expect($keys[0])->toBe('1=2E50~z1')
            ->and(fn (): Testable => $this->createLiveComponent(AttachableRelationTable::class, AttachableRelationTable::props($this->entityManager(), $manifest, 'tariffBands', 'label', ['multiple', 'permissive']))
                ->callAction(TestAction::make('attach')->table(), data: ['recordId' => [...$keys, '1=2E5~z1']]))
            ->toThrow(LogicException::class, 'The action [attach] was asked to attach a record its select never offered')
            ->and($this->reload($manifest)->tariffBands)->toHaveCount(0);
    });
});
