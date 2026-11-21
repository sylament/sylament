<?php

declare(strict_types=1);

use Doctrine\ORM\Exception\EntityMissingAssignedId;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\RecordActionsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\CharmSatchel;
use Sylament\Tests\Fixtures\Entity\ClaspedLocket;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderCrock;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\QuayMooring;
use Sylament\Tests\Fixtures\Entity\SatchelCharm;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Entity\VolumeSpine;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\LarderCrockTable;
use Sylament\Tests\Fixtures\Tables\SecondManagerSpineTable;

use function Symfony\Component\Translation\t;

describe('the trigger', function (): void {
    it('reads "New" and its record label, and the bare verb when nothing names a record class', function (): void {
        expect($this->renderViewComponent(CreateAction::make()->recordClass(CappedHeadline::class)))->toContainElement('button', 'New capped headline')
            ->and($this->renderViewComponent(CreateAction::make()))->toContainElement('button', 'Create');
    });

    it('translates its label and its modal heading around a record label given as a message', function (): void {
        $action = fn (): CreateAction => CreateAction::make()->recordClass(CappedHeadline::class)->recordLabel(t('record.label', [], 'fixtures-actions'));

        expect($this->renderViewComponent($action()))->toContainElement('button', 'New crate')
            ->and($this->renderTwigComponent('SylamentActions:ActionModal', ['action' => $action(), 'id' => 'create-modal'])->crawler())
            ->toContainElement('h2', 'Create Crate');
    });

    it('shows a plus inside a dropdown and no icon as a button of its own', function (): void {
        $grouped = $this->renderViewComponent(ActionGroup::make([CreateAction::make()->recordClass(CappedHeadline::class)]));
        $bare = $this->renderViewComponent(CreateAction::make()->recordClass(CappedHeadline::class));

        expect($grouped)->toContainElement(SupportSelectors::DROPDOWN_ITEM . ' ' . SupportSelectors::ICON, 'plus-20-solid')
            ->and($bare)->toContainElement('button', 'New capped headline')
            ->and($bare->filter(SupportSelectors::ICON))->toHaveCount(0);
    });

    it('links to the page its host routes it to, and opens a modal when forced to or when its class is not routed', function (): void {
        $tester = $this->createLiveComponent(RecordActionsHost::class);

        expect($tester->render()->crawler())->toContainElement('a[href="/headlines/linkedCreate"]', 'New capped headline')
            ->and($tester->render()->crawler()->filter('a[href="/headlines/linkedCreate"][data-action]'))->toHaveCount(0)
            ->and($tester->render()->crawler())->toContainElement('button' . Selectors::liveParameter('name', 'linkedForced'), 'New capped headline')
            ->and($tester->render()->crawler())->toContainElement('button' . Selectors::liveParameter('name', 'linkedArticle'), 'New article');

        $tester->mountAction('linkedForced');

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Create Capped Headline');
    });
});

describe('the modal', function (): void {
    it('is headed "Create" and its title-cased record label, and offers Create, Create & create another and Cancel', function (): void {
        $tester = $this->createLiveComponent(RecordActionsHost::class)->mountAction('create');

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Create Capped Headline')
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Create', 'Create & create another', 'Cancel']);
    });

    it('drops the create-another button with createAnother(false), and createAnotherAction() reshapes it', function (): void {
        expect($this->createLiveComponent(RecordActionsHost::class)->mountAction('createLone')->render()->crawler())
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Create', 'Cancel'])
            ->and($this->createLiveComponent(RecordActionsHost::class)->mountAction('createRelabelled')->render()->crawler())
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Create', 'Create & add another', 'Cancel']);
    });

    it('creates a record of its class from the modal in one transaction, and toasts "Created"', function (): void {
        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('create', data: ['code' => 'MOON', 'title' => 'Moon', 'slug' => 'moon'])
            ->assertActionNotMounted()
            ->assertNotified('Created');

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))->toBe(['"START TRANSACTION"', 'INSERT INTO CappedHeadline', '"COMMIT"'])
            ->and($this->reload(new CappedHeadline('MOON')))->toBeRecord(title: 'Moon', slug: 'moon');
    });

    it("answers the record's own constraints as field errors and writes nothing", function (): void {
        $this->seed(new CappedHeadline('SUN', 'Sun', slug: 'taken'));

        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('create', data: ['code' => 'MOON', 'title' => 'Far too long', 'slug' => 'taken'])
            ->assertHasActionErrors(['title', 'slug'])
            ->assertActionMounted('create');

        expect($this->entityManager()->find(CappedHeadline::class, 'MOON'))->toBeNull();
    });

    it('creates another: saves, toasts, keeps the preserved field, empties the rest and stays mounted', function (): void {
        $tester = $this->createLiveComponent(RecordActionsHost::class)
            ->mountAction('create')
            ->setActionData(['code' => 'MOON', 'title' => 'Moon', 'slug' => 'moon'])
            ->callMountedAction(['another' => true])
            ->assertNotified('Created')
            ->assertActionMounted('create')
            ->assertActionDataSet(['code' => null, 'title' => 'Moon', 'slug' => null]);

        expect($this->dispatchedBrowserEvents($tester, 'reset-schema-component-state'))->toBe([['schemaKey' => 'mountedActionSchema0']])
            ->and($this->reload(new CappedHeadline('MOON')))->toBeRecord(title: 'Moon');

        $tester->setActionData(['code' => 'STAR', 'slug' => 'star'])->callMountedAction();

        expect($this->reload(new CappedHeadline('STAR')))->toBeRecord(title: 'Moon');
    });

    it("creates beside a page's record without writing into it", function (): void {
        $this->seed(new CappedHeadline('PAGE', 'Page', slug: 'page'));

        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createBesidePage', data: ['code' => 'MOON', 'title' => 'Moon', 'slug' => 'moon'])
            ->assertActionNotMounted();

        expect($this->reload(new CappedHeadline('PAGE')))->toBeRecord(title: 'Page')
            ->and($this->reload(new CappedHeadline('MOON')))->toBeRecord(title: 'Moon');
    });

    it('refuses by name a record class whose constructor requires arguments, advising using()', function (): void {
        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createProduct', data: ['name' => 'Lamp']))
            ->toThrow(LogicException::class, 'Cannot map a new record for the [createProduct] action: ' . Product::class . " requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or build it in the action's using() closure.");

        expect($this->entityManager()->getRepository(Product::class)->count())->toBe(0);
    });

    it('refuses by name a create that nothing names a record class for, advising where one comes from', function (): void {
        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createClassless', data: ['title' => 'Moon']))
            ->toThrow(LogicException::class, 'The action [createClassless] creates a record but nothing names its class — call recordClass(), host it on a query() table or a resource page, or bind the schema with record().');
    });

    it("asks the record check of the reader its schema holds, rebound to the table query's manager rather than the registry's", function (): void {
        $this->createLiveComponent(SecondManagerSpineTable::class)
            ->callAction(TestAction::make('create')->table(), data: ['code' => 'MOON', 'label' => 'Moon'])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['key check on a ' . VolumeSpine::class . ' by the reader rebound to the second manager'])
            ->and($this->entityManager()->find(VolumeSpine::class, 'MOON')?->label)->toBe('Moon');
    });
});

describe('a key the modal leaves blank', function (): void {
    it('is refused by name after persist(), and nothing is written', function (): void {
        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createMooring', data: ['label' => 'East']))
            ->toThrow(LogicException::class, 'The [createMooring] action built a [' . QuayMooring::class . '] whose identifier is still blank after persist(), so it was not saved.');

        expect($this->entityManager()->getRepository(QuayMooring::class)->count())->toBe(0);
    });

    it("is refused by name even when its whole key flattens to '', which Doctrine cannot detach", function (): void {
        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createLocket', data: ['label' => 'Gold']))
            ->toThrow(LogicException::class, 'The [createLocket] action built a [' . ClaspedLocket::class . '] whose identifier is still blank after persist(), so it was not saved.');

        expect($this->entityManager()->getRepository(ClaspedLocket::class)->count())->toBe(0);
    });

    it('is saved once a prePersist callback fills it', function (): void {
        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createBulletin', data: ['scope' => 'news', 'label' => 'Morning'])
            ->assertActionNotMounted();

        $saved = new StampedBulletin();
        $saved->scope = 'news';
        $saved->stamp = 'auto';

        expect($this->reload($saved))->toBeRecord(label: 'Morning');
    });

    it('mapped by a field, is refused under that field', function (): void {
        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createStampedBulletin', data: ['scope' => 'news', 'stamp' => '', 'label' => 'Morning'])
            ->assertHasActionErrors(['stamp']);

        expect($this->entityManager()->getRepository(StampedBulletin::class)->count())->toBe(0);
    });

    it('typed as whitespace under validateRecordKey(false), is refused by name with advice to switch the key rules back on', function (): void {
        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createLooseMooring', data: ['pier' => ' ', 'slot' => ' ', 'label' => 'East']))
            ->toThrow(LogicException::class, 'The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.');

        expect($this->entityManager()->getRepository(QuayMooring::class)->count())->toBe(0);
    });
});

describe('a create on a relationship', function (): void {
    it("joins the table's relationship, the action's own, or the host's, in the flush that writes the record", function (): void {
        $this->seed($cellar = new Larder('Cellar'), $annex = new Larder('Annex'));

        $this->createLiveComponent(LarderCrockTable::class)
            ->callAction(TestAction::make('create')->table(), data: ['shelf' => 'top', 'label' => 'Jam'])
            ->assertActionNotMounted()
            ->assertNotified('Created');

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))->toBe(['"START TRANSACTION"', 'INSERT INTO LarderCrock', '"COMMIT"']);

        $this->createLiveComponent(LarderCrockTable::class)
            ->callAction(TestAction::make('createAnnexed')->table(), data: ['shelf' => 'top', 'label' => 'Pickle'])
            ->assertActionNotMounted();

        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createCrockByHost', data: ['shelf' => 'low', 'label' => 'Honey'])
            ->assertActionNotMounted();

        expect($this->reload(new LarderCrock($cellar, 'top')))->toBeRecord(label: 'Jam')
            ->and($this->reload(new LarderCrock($annex, 'top')))->toBeRecord(label: 'Pickle')
            ->and($this->reload(new LarderCrock($cellar, 'low')))->toBeRecord(label: 'Honey');
    });

    it("joins a composite-keyed owner's many-to-many, writing the record and its membership in one transaction", function (string $action, object $owner, string $joinTable): void {
        $this->seed($owner);

        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction($action, data: ['name' => 'Fern'])
            ->assertActionNotMounted();

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))->toBe(['"START TRANSACTION"', 'INSERT INTO Plant', "INSERT INTO {$joinTable}", '"COMMIT"'])
            ->and($this->reload($owner)->plants->map(static fn (Plant $plant): string => $plant->name)->toArray())->toBe(['Fern']);
    })->with([
        'scalar fields' => fn (): array => ['createTerracePlant', new Terrace(estate: 7, number: 2), 'terrace_plant'],
        'an association plus a scalar' => fn (): array => ['createAllotmentPlant', new Allotment(new Garden(), number: 2), 'allotment_plant'],
    ]);

    it('refuses a key taken under the owner as a field error', function (): void {
        $this->seed($cellar = new Larder('Cellar'), new LarderCrock($cellar, 'top', 'Jam'));

        $this->createLiveComponent(LarderCrockTable::class)
            ->callAction(TestAction::make('create')->table(), data: ['shelf' => 'top', 'label' => 'Again'])
            ->assertHasActionErrors(['shelf'])
            ->assertActionMounted();

        expect($this->reload(new LarderCrock($cellar, 'top')))->toBeRecord(label: 'Jam');
    });

    it('lands under the relationship when its form picks another owner, refusing a key the pick holds', function (): void {
        $this->seed($cellar = new Larder('Cellar'), $annex = new Larder('Annex'), new LarderCrock($annex, 'low', 'Taken'));

        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createCrockPicked', data: ['larder' => (string) $annex->id, 'shelf' => 'top', 'label' => 'Jam'])
            ->assertActionNotMounted();

        expect($this->reload(new LarderCrock($cellar, 'top')))->toBeRecord(label: 'Jam');

        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createCrockPicked', data: ['larder' => (string) $annex->id, 'shelf' => 'low', 'label' => 'Jam'])
            ->assertHasActionErrors(['shelf']);
    });

    it("gives a many-to-many owner's collection the record back when the mapping, persist() or the blank-key read refuses it", function (string $action, array $data, ?string $refusal, ?string $message): void {
        $this->seed($satchel = new CharmSatchel('Red'));

        $tester = $this->createLiveComponent(RecordActionsHost::class);

        if ($refusal === null) {
            $tester->callAction($action, data: $data)->assertHasActionErrors(['label']);
        } else {
            expect(fn () => $tester->callAction($action, data: $data))->toThrow($refusal, $message);
        }

        $managed = $this->entityManager()->find(CharmSatchel::class, $satchel->id);

        expect($managed?->charms)->toHaveCount(0)
            ->and($this->entityManager()->flush())->toBeNull()
            ->and($this->entityManager()->getRepository(SatchelCharm::class)->count())->toBe(0);
    })->with([
        'the mapping' => ['createCharm', ['tier' => 1, 'code' => 'opal', 'label' => 'Far too long'], null, null],
        "persist(), Doctrine's refusal let through" => ['createCharmUntiered', ['code' => 'opal', 'label' => 'Opal'], EntityMissingAssignedId::class, "is missing an assigned ID for field  'tier'"],
        'the blank-key read' => ['createCharmUncoded', ['tier' => 1, 'label' => 'Opal'], LogicException::class, 'whose identifier is still blank after persist()'],
    ]);

    it('refuses by name a record class whose constructor requires arguments, advising using()', function (): void {
        $lighthouse = new Lighthouse();
        $lighthouse->lamp = new Product('Beacon');
        $this->seed($lighthouse->lamp, $lighthouse);

        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createSpare', data: ['name' => 'Lamp']))
            ->toThrow(LogicException::class, 'Cannot map a new record for the [createSpare] action: ' . Product::class . " requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or build it in the action's using() closure.");

        expect($this->entityManager()->getRepository(Product::class)->count())->toBe(1);
    });
});

describe('setUp()', function (): void {
    it('clears a record a configureUsing() closure on Action bound', function (): void {
        $configured = Action::configureUsing(
            fn (Action $action): Action => $action->record(new CappedHeadline('PAGE')),
            during: fn (): array => [CreateAction::make(), Action::make('open')],
        );

        expect($configured[0]->getRecord())->toBeNull()
            ->and($configured[1]->getRecord())->toBeInstanceOf(CappedHeadline::class);
    });
});
