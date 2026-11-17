<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Tests\Fixtures\Actions\RecordActionsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\QuayMooring;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Schemas\HookLog;

describe('the trigger', function (): void {
    it('reads "New" and its record label, and the bare verb when nothing names a record class', function (): void {
        expect($this->renderViewComponent(CreateAction::make()->recordClass(CappedHeadline::class)))->toContainElement('button', 'New capped headline')
            ->and($this->renderViewComponent(CreateAction::make()))->toContainElement('button', 'Create');
    });

    it('shows a plus inside a dropdown and no icon as a button of its own', function (): void {
        $grouped = $this->renderViewComponent(ActionGroup::make([CreateAction::make()->recordClass(CappedHeadline::class)]));
        $bare = $this->renderViewComponent(CreateAction::make()->recordClass(CappedHeadline::class));

        expect($grouped)->toContainElement('.sy-dropdown-item svg', 'plus-20-solid')
            ->and($bare)->toContainElement('button', 'New capped headline')
            ->and($bare->filter('button svg'))->toHaveCount(0);
    });

    it('links to the page its host routes it to, and opens a modal when forced to or when its class is not routed', function (): void {
        $tester = $this->createLiveComponent(RecordActionsHost::class);

        expect($tester->render()->crawler())->toContainElement('a[href="/headlines/linkedCreate"]', 'New capped headline')
            ->and($tester->render()->crawler()->filter('a[href="/headlines/linkedCreate"][data-action]'))->toHaveCount(0)
            ->and($tester->render()->crawler())->toContainElement('button[data-live-name-param="linkedForced"]', 'New capped headline')
            ->and($tester->render()->crawler())->toContainElement('button[data-live-name-param="linkedArticle"]', 'New article')
            ->and($this->renderViewComponent(CreateAction::make()->recordClass(CappedHeadline::class)->url('/headlines/new')))->toContainElement('a[href="/headlines/new"]', 'New capped headline');

        $tester->mountAction('linkedForced');

        expect($tester->render()->crawler())->toContainElement(Selectors::MODAL_HEADING, 'Create Capped Headline');
    });
});

describe('the modal', function (): void {
    it('is headed "Create" and its title-cased record label, and offers Create, Create & create another and Cancel', function (): void {
        $tester = $this->createLiveComponent(RecordActionsHost::class)->mountAction('create');

        expect($tester->render()->crawler())->toContainElement(Selectors::MODAL_HEADING, 'Create Capped Headline')
            ->toContainButtons(Selectors::MODAL_FOOTER_ACTIONS, ['Create', 'Create & create another', 'Cancel']);
    });

    it('drops the create-another button with createAnother(false), and createAnotherAction() reshapes it', function (): void {
        expect($this->createLiveComponent(RecordActionsHost::class)->mountAction('createLone')->render()->crawler())
            ->toContainButtons(Selectors::MODAL_FOOTER_ACTIONS, ['Create', 'Cancel'])
            ->and($this->createLiveComponent(RecordActionsHost::class)->mountAction('createRelabelled')->render()->crawler())
            ->toContainButtons(Selectors::MODAL_FOOTER_ACTIONS, ['Create', 'Create & add another', 'Cancel']);
    });

    it('creates a record of its class from the modal in one transaction, and toasts "Created"', function (): void {
        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('create', data: ['code' => 'MOON', 'title' => 'Moon', 'slug' => 'moon'])
            ->assertActionNotMounted()
            ->assertNotified('Created');

        expect(array_slice($this->statementsRun(), -3))->toBe(['"START TRANSACTION"', 'INSERT INTO CappedHeadline (code, title, slug) VALUES (?, ?, ?)', '"COMMIT"'])
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
            ->toThrow(LogicException::class, "build it in the action's using() closure.");

        expect($this->entityManager()->getRepository(Product::class)->count())->toBe(0);
    });

    it('asks the record check of the reader its schema holds, rebound to the manager it writes through', function (): void {
        $this->createLiveComponent(RecordActionsHost::class)
            ->callAction('createWatched', data: ['code' => 'MOON', 'title' => 'Moon', 'slug' => 'moon'])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['key check on a ' . CappedHeadline::class . ' by the rebound reader']);
    });
});

describe('a key the modal leaves blank', function (): void {
    it('is refused by name after persist(), and nothing is written', function (): void {
        expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createMooring', data: ['label' => 'East']))
            ->toThrow(LogicException::class, 'The [createMooring] action built a [' . QuayMooring::class . '] whose identifier is still blank after persist(), so it was not saved.');

        expect($this->entityManager()->getRepository(QuayMooring::class)->count())->toBe(0);
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
