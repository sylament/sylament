<?php

declare(strict_types=1);

use Sylament\Actions\ActionGroup;
use Sylament\Actions\ReplicateAction;
use Sylament\Tests\Fixtures\Actions\ReplicateHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierPigment;
use Sylament\Tests\Fixtures\Entity\AtelierStock;
use Sylament\Tests\Fixtures\Entity\BlankCodedReel;
use Sylament\Tests\Fixtures\Entity\CallbackCodedReel;
use Sylament\Tests\Fixtures\Entity\ConstructorCodedBadge;
use Sylament\Tests\Fixtures\Entity\Greenhouse;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\Uid\Uuid;

describe('the trigger', function (): void {
    it('reads "Replicate" with two squares, on its own and inside a dropdown', function (): void {
        $bare = $this->renderViewComponent(ReplicateAction::make());
        $grouped = $this->renderViewComponent(ActionGroup::make([ReplicateAction::make()]));

        expect($bare)->toContainElement('button', 'square-2-stack-20-solidReplicate')
            ->and($bare)->toContainElement(SupportSelectors::ICON, 'square-2-stack-20-solid')
            ->and($grouped)->toContainElement(SupportSelectors::DROPDOWN_ITEM . ' ' . SupportSelectors::ICON, 'square-2-stack-20-solid');
    });
});

describe('the modal', function (): void {
    it("is headed \"Replicate\" and its record's title, offers Replicate and Cancel, and is filled from the original", function (): void {
        $this->seed(new Article('Sun'));

        $tester = $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicate')
            ->assertActionDataSet(['title' => 'Sun']);

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Replicate Sun')
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Replicate', 'Cancel']);
    });

    it('is headed by the bare verb when nothing names its record', function (): void {
        expect($this->createLiveComponent(ReplicateHost::class)->mountAction('replicateUnbound')->render()->crawler())
            ->toContainElement(SupportSelectors::MODAL_HEADING, 'Replicate');
    });

    it('leaves an excluded name out of the fill, and fills what mutateRecordDataUsing() answers', function (): void {
        $this->seed(new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicateExcluded')
            ->assertActionDataSet(['title' => null]);

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicateMutated')
            ->assertActionDataSet(['title' => 'Sun (copy)']);
    });

    it('fills only the paths its schema declares, and carries an undeclared column onto the replica', function (): void {
        $this->seed(new PropagatedCutting('Rose', nursery: 'Kew'));

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicateCutting')
            ->assertActionDataSet(function (array $state): array {
                expect($state)->toBe(['name' => 'Rose']);

                return [];
            })
            ->setActionData(['name' => 'Rose cutting'])
            ->callMountedAction()
            ->assertActionNotMounted();

        $replica = $this->entityManager()->getRepository(PropagatedCutting::class)->findOneBy(['name' => 'Rose cutting']);

        expect($replica?->origin->nursery)->toBe('Kew');
    });

    it("blanks an excluded embeddable's owner in the modal, while an excluded path inside it is still filled and saved", function (): void {
        $this->seed(new PropagatedCutting('Rose', nursery: 'Kew'));

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicateCuttingOriginExcluded')
            ->assertActionDataSet(['origin.nursery' => null]);

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicateCuttingNurseryExcluded')
            ->assertActionDataSet(['origin.nursery' => 'Kew'])
            ->setActionData(['name' => 'Rose cutting'])
            ->callMountedAction()
            ->assertActionNotMounted();

        $replica = $this->entityManager()->getRepository(PropagatedCutting::class)->findOneBy(['name' => 'Rose cutting']);

        expect($replica?->origin->nursery)->toBe('Kew');
    });

    it("checks a unique() field that ignores its record against the original too, since the modal's record is the class", function (): void {
        $this->seed(new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction('replicateUnique')
            ->callMountedAction()
            ->assertHasActionErrors(['title'])
            ->assertActionMounted('replicateUnique');

        expect($this->entityManager()->getRepository(Article::class)->count())->toBe(1);
    });

    it("enables a field disabledOn('edit'), since the modal's operation is the action's name", function (): void {
        $this->seed(new Article('Sun'));

        expect($this->createLiveComponent(ReplicateHost::class)->mountAction('replicateDisabledOnEdit')->render()->crawler())
            ->toContainElement('dialog[open] input[name="title"]:not([disabled])');
    });
});

describe('the write', function (): void {
    it("saves the modal's values as a new row, leaves the original alone, closes the modal and toasts \"Replicated\"", function (): void {
        $this->seed($sun = new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)
            ->callAction('replicate', data: ['title' => 'Moon'])
            ->assertActionNotMounted()
            ->assertNotified('Replicated');

        $replica = $this->entityManager()->getRepository(Article::class)->findOneBy(['title' => 'Moon']);

        expect($replica?->id)->not->toBeNull()->not->toBe($sun->id)
            ->and($this->reload($sun))->toBeRecord(title: 'Sun');
    });

    it('copies a record through a confirmation-shaped modal when it declares no schema', function (): void {
        $this->seed(new Article('Sun'));

        $tester = $this->createLiveComponent(ReplicateHost::class)->mountAction('replicateBare');

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Replicate Sun')
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Replicate', 'Cancel']);

        $tester->callMountedAction()->assertNotified('Replicated');

        expect($this->entityManager()->getRepository(Article::class)->findBy(['title' => 'Sun']))->toHaveCount(2);
    });

    it('rewrites the copy of a modal-less action with mutateRecordDataUsing()', function (): void {
        $this->seed($sun = new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateBareMutated');

        expect($this->entityManager()->getRepository(Article::class)->findBy(['title' => 'Sun (copy)']))->toHaveCount(1)
            ->and($this->reload($sun))->toBeRecord(title: 'Sun');
    });

    it("refuses by name a modal-less mutator's field the copy rules decline, and writes nothing", function (): void {
        $this->seed(new Article('Sun'));

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction('replicateBareMistyped'))
            ->toThrow(LogicException::class, Article::class . ' has no copyable mapped field [id].');

        expect($this->entityManager()->getRepository(Article::class)->count())->toBe(1);
    });

    it('leaves an excluded field or association off the copy, which keeps its default', function (): void {
        $tally = new Tally();
        $tally->count = 5;

        $graft = new PropagatedCutting('Graft');
        $graft->parent = new PropagatedCutting('Rootstock');

        $rootstock = $graft->parent;
        $this->seed($tally, $rootstock, $graft);

        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateTallyExcluded');
        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateCuttingOrphaned');

        expect($this->entityManager()->find(Tally::class, 2))->toBeRecord(count: 0)
            ->and($this->reload($tally))->toBeRecord(count: 5)
            ->and($this->entityManager()->find(PropagatedCutting::class, 3))->toBeRecord(name: 'Graft', parent: null)
            ->and($this->reload($graft)->parent?->name)->toBe('Rootstock');
    });
});

describe('the hooks', function (): void {
    it('runs beforeReplicaSaved() on the unsaved replica once the modal is mapped, and saves what it sets', function (): void {
        $this->seed(new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateHooked', data: ['title' => 'Moon']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeReplicaSaved() got Moon, unsaved'])
            ->and($this->entityManager()->getRepository(Article::class)->findBy(['title' => 'Hooked']))->toHaveCount(1);
    });

    it('hands after() the saved replica', function (): void {
        $this->seed(new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateAfter');

        $replica = $this->entityManager()->getRepository(Article::class)->findOneBy([], ['id' => 'DESC']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([sprintf('after() got replica %d', $replica?->id)])
            ->and($replica?->id)->toBe(2);
    });

    it('hands a using() closure the data, the record and the modal schema, or no schema without a modal, in place of the write', function (): void {
        $this->seed(new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateUsing', data: ['title' => 'Moon']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['using() got [title] and a ' . Article::class . ', with a schema holding {"title":"Moon"}']);

        $this->createLiveComponent(ReplicateHost::class)->callAction('replicateBareUsing');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['using() got [] and a ' . Article::class . ', with no schema'])
            ->and($this->entityManager()->getRepository(Article::class)->count())->toBe(1);
    });

    it('refuses by name a path a hook adds that no component maps, writing nothing, and saves a path it overwrites', function (): void {
        $this->seed(new Article('Sun'));

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction('replicateUnmapped', data: ['title' => 'Moon']))
            ->toThrow(LogicException::class, 'The [replicateUnmapped] action added [unmapped] in mutateDataUsing(), but no component of its schema maps those paths');

        expect($this->entityManager()->getRepository(Article::class)->count())->toBe(1);

        $this->createLiveComponent(ReplicateHost::class)
            ->callAction('replicateOverwritten', data: ['title' => 'Moon'])
            ->assertActionNotMounted();

        expect($this->entityManager()->getRepository(Article::class)->findBy(['title' => 'By the hook']))->toHaveCount(1);
    });

    it('asks the record check of the reader its schema holds, rebound to the manager it writes through', function (): void {
        $this->seed(new Article('Sun'));

        $this->createLiveComponent(ReplicateHost::class)
            ->callAction('replicateWatched', data: ['title' => 'Moon'])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['key check on a ' . Article::class . ' by the rebound reader']);
    });
});

describe('the key', function (): void {
    it("saves a replica whose modal changes its key, keeping an identifier association's parent", function (string $class, object $line): void {
        $this->seed($line);

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction("replicate{$class}")
            ->assertActionDataSet(['line' => 2])
            ->setActionData(['line' => 3])
            ->callMountedAction()
            ->assertActionNotMounted();

        expect($this->reload(match ($line::class) {
            OrderLine::class => new OrderLine(order: 7, line: 3),
            InvoiceLine::class => new InvoiceLine($line->invoice, line: 3),
        }))->toBeRecord(note: 'Bolts')
            ->and($this->reload($line))->toBeRecord(line: 2, note: 'Bolts');
    })->with([
        'scalar fields' => fn (): array => ['OrderLine', new OrderLine(order: 7, line: 2, note: 'Bolts')],
        'an association plus a scalar' => fn (): array => ['InvoiceLine', new InvoiceLine(new Invoice(7), line: 2, note: 'Bolts')],
    ]);

    it("answers a modal left on its original's key under every key field it maps, writing nothing", function (string $class, object $line, array $errors): void {
        $this->seed($line);

        $this->createLiveComponent(ReplicateHost::class)
            ->mountAction("replicate{$class}")
            ->callMountedAction()
            ->assertHasActionErrors($errors)
            ->assertActionMounted("replicate{$class}");

        expect($this->entityManager()->getRepository($line::class)->count())->toBe(1);
    })->with([
        'scalar fields' => fn (): array => ['OrderLine', new OrderLine(order: 7, line: 2, note: 'Bolts'), ['order' => 'This value is already used.', 'line' => 'This value is already used.']],
        'an association plus a scalar' => fn (): array => ['InvoiceLine', new InvoiceLine(new Invoice(7), line: 2, note: 'Bolts'), ['line' => 'This value is already used.']],
    ]);

    it("answers in the modal a key its beforeReplicaSaved() hook moves onto another record's, writing nothing", function (string $class, object $line, object $holder): void {
        $this->seed($line, $holder);

        $this->createLiveComponent(ReplicateHost::class)
            ->callAction("replicate{$class}ModalShifted", data: ['line' => 4])
            ->assertHasActionErrors(['line' => 'This value is already used.']);

        expect($this->entityManager()->getRepository($line::class)->count())->toBe(2);
    })->with([
        'scalar fields' => fn (): array => ['OrderLine', new OrderLine(order: 7, line: 2, note: 'Bolts'), new OrderLine(order: 7, line: 3, note: 'Nuts')],
        'an association plus a scalar' => fn (): array => ['InvoiceLine', new InvoiceLine($invoice = new Invoice(7), line: 2, note: 'Bolts'), new InvoiceLine($invoice, line: 3, note: 'Nuts')],
    ]);

    it('refuses by name, with no modal, a copy whose key is still incomplete, writing nothing', function (string $action, Closure $seed, string $class): void {
        $this->seed(...$seed());

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction($action))
            ->toThrow(LogicException::class, "The [{$action}] action built a replica of [{$class}] whose identifier is incomplete, so it cannot be saved as a new record.");

        expect($this->entityManager()->getRepository($class)->count())->toBe(1);
    })->with([
        'scalar fields' => ['replicateOrderLineBare', static fn (): array => [new OrderLine(order: 7, line: 2, note: 'Bolts')], OrderLine::class],
        'an association plus a scalar' => ['replicateInvoiceLineBare', static fn (): array => [new InvoiceLine(new Invoice(7), line: 2, note: 'Bolts')], InvoiceLine::class],
        'a single derived identity' => ['replicateVentBare', static fn (): array => [$greenhouse = new Greenhouse(Uuid::v7()), new GreenhouseVent($greenhouse)], GreenhouseVent::class],
    ]);

    it('refuses by name, with no modal, a copy that keeps a key made only of associations, and answers a modal mapping no key field as a record error', function (): void {
        $this->seed($atelier = new Atelier('North'), $ochre = new AtelierPigment('Ochre'), $stock = new AtelierStock($atelier, $ochre, tubes: 4));

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction('replicateStockBare'))
            ->toThrow(LogicException::class, 'The [replicateStockBare] action built a replica of [' . AtelierStock::class . '] whose key another record already holds, so it cannot be saved as a new record.');

        $tester = $this->createLiveComponent(ReplicateHost::class)
            ->callAction('replicateStockTubes', data: ['tubes' => 9])
            ->assertActionMounted('replicateStockTubes');

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::OPEN_MODAL . ' ' . Selectors::RECORD_ERROR, 'This value is already used.')
            ->and($this->entityManager()->getRepository(AtelierStock::class)->count())->toBe(1)
            ->and($this->reload($stock))->toBeRecord(tubes: 4);
    });

    it('saves the key a beforeReplicaSaved() hook gives a modal-less copy, and refuses it by name once another record holds it', function (string $class, object $line): void {
        $this->seed($line);

        $this->createLiveComponent(ReplicateHost::class)->callAction("replicate{$class}Shifted");

        expect($this->entityManager()->getRepository($line::class)->findBy(['line' => 3]))->toHaveCount(1);

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction("replicate{$class}Shifted"))
            ->toThrow(LogicException::class, "The [replicate{$class}Shifted] action built a replica of [" . $line::class . "] whose key another record already holds, so it cannot be saved as a new record.");

        expect($this->entityManager()->getRepository($line::class)->count())->toBe(2);
    })->with([
        'scalar fields' => fn (): array => ['OrderLine', new OrderLine(order: 7, line: 2, note: 'Bolts')],
        'an association plus a scalar' => fn (): array => ['InvoiceLine', new InvoiceLine(new Invoice(7), line: 2, note: 'Bolts')],
    ]);

    it('saves a replica whose blank key half a prePersist callback fills', function (): void {
        $bulletin = new StampedBulletin();
        $bulletin->scope = 'news';
        $bulletin->stamp = 'morning';
        $bulletin->label = 'Weather';
        $this->seed($bulletin);

        $this->createLiveComponent(ReplicateHost::class)
            ->callAction('replicateBulletin', data: ['scope' => 'sport'])
            ->assertActionNotMounted();

        $saved = new StampedBulletin();
        $saved->scope = 'sport';
        $saved->stamp = 'auto';

        expect($this->reload($saved))->toBeRecord(label: 'Weather');
    });

    it("refuses by name a single string key left blank, before persist() when no hook could fill it, and after it, still scheduled, when a callback leaves it blank", function (): void {
        $blank = new BlankCodedReel();
        $blank->code = 'R-1';

        $hooked = new CallbackCodedReel();
        $hooked->code = 'R-1';
        $this->seed($blank, $hooked);

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction('replicateBlankReelBare'))
            ->toThrow(LogicException::class, 'The [replicateBlankReelBare] action built a replica of [' . BlankCodedReel::class . '] whose identifier is incomplete');

        expect($this->entityManager()->getUnitOfWork()->getScheduledEntityInsertions())->toBe([]);

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction('replicateCallbackReelBare'))
            ->toThrow(LogicException::class, 'The [replicateCallbackReelBare] action built a replica of [' . CallbackCodedReel::class . '] whose identifier is incomplete');

        expect(array_map(static fn (object $entity): string => $entity::class, array_values($this->entityManager()->getUnitOfWork()->getScheduledEntityInsertions())))->toBe([CallbackCodedReel::class])
            ->and($this->entityManager()->getConnection()->fetchAllAssociative('SELECT code FROM BlankCodedReel UNION ALL SELECT code FROM CallbackCodedReel'))->toBe([['code' => 'R-1'], ['code' => 'R-1']]);
    });

    it('refuses by name a modal mapping a key only the constructor sets, and writes nothing', function (): void {
        $this->seed(new ConstructorCodedBadge('B-1', 'Gold'));

        expect(fn () => $this->createLiveComponent(ReplicateHost::class)->callAction('replicateBadge', data: ['code' => 'B-2']))
            ->toThrow(LogicException::class, 'Cannot map state path "code" onto ' . ConstructorCodedBadge::class . ': no setter can change that property, and it holds no value yet');

        expect($this->entityManager()->getRepository(ConstructorCodedBadge::class)->count())->toBe(1);
    });
});
