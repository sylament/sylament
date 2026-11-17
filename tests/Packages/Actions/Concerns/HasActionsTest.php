<?php

declare(strict_types=1);

use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\ActionStackHost;
use Sylament\Tests\Fixtures\Actions\AtelierResolvingHost;
use Sylament\Tests\Fixtures\Actions\MemoDefaultsHost;
use Sylament\Tests\Fixtures\Actions\SchemaDeclarationHost;
use Sylament\Tests\Fixtures\Actions\SchemaModalHost;
use Sylament\Tests\Fixtures\Actions\SchemaRecordHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Tables\DeletingProductTable;
use Sylament\Tests\Fixtures\Tables\OrderLineTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Sylament\Tests\Fixtures\Tables\StockedProductTable;
use Symfony\UX\LiveComponent\Exception\HydrationException;

describe('mounting', function (): void {
    it('pushes a frame of the name, arguments and context, opens its dialog and asks the browser to open it', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm', ['tone' => 'loud']);
        $dialog = $tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

        expect($tester->component()->mountedActions)->toBe([['name' => 'confirm', 'arguments' => ['tone' => 'loud'], 'context' => []]])
            ->and($dialog->text())->toContain('Confirm the run')
            ->and($this->dispatchedBrowserEvents($tester, 'sylament-open-modal'))->toBe([['id' => $dialog->attr('id')]]);
    });

    it('pushes a second frame for a nested mount under one host id, closing the parent behind a child that does not overlay it', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('details');
        $crawler = $tester->render()->crawler();
        $parentId = (string) $crawler->filter(Selectors::mountedModal(0))->attr('id');

        expect(array_column($tester->component()->mountedActions, 'name'))->toBe(['confirm', 'details'])
            ->and($crawler->filter(Selectors::mountedModal(1))->attr('id'))->toBe(mb_substr($parentId, 0, -1) . '1')
            ->and($crawler->filter(Selectors::mountedModal(0) . Selectors::OPEN)->count())->toBe(0)
            ->and($crawler->filter(Selectors::mountedModal(1) . Selectors::OPEN)->text())->toContain('Details');

        $crawler = $tester->unmountAction()->mountAction('overlay')->render()->crawler();

        expect($crawler->filter(Selectors::mountedModal(0))->attr('id'))->toBe($parentId)
            ->and($crawler->filter(Selectors::mountedModal(0) . Selectors::OPEN)->count())->toBe(1)
            ->and($crawler->filter(Selectors::mountedModal(1) . Selectors::OPEN)->text())->toContain('Overlay');
    });

    it('refuses a client write to the stack while its data bag takes one', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->set('mountedActionsData', [0 => ['tone' => 'loud']]);

        expect($tester->component()->mountedActionsData)->toBe([0 => ['tone' => 'loud']])
            ->and(fn () => $tester->set('mountedActions', []))->toThrow(HydrationException::class, 'The model "mountedActions" was sent for update, but it is not writable.');
    });

    it('runs a modal-less action at once, toasting and leaving nothing mounted or shown', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('quick');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['quick', 'afterActionCalled: quick'])
            ->and($tester->render()->crawler()->filter(Selectors::MOUNTED_MODAL)->count())->toBe(0);

        $tester->assertNotified('Done quickly')->assertActionNotMounted();
    });

    it('unmounts an action that halts or cancels while mounting, or is disabled, and opens nothing', function (string $name): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction($name)->assertActionNotMounted();

        expect($tester->render()->crawler()->filter(Selectors::MOUNTED_MODAL)->count())->toBe(0)
            ->and($this->dispatchedBrowserEvents($tester, 'sylament-open-modal'))->toBe([])
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        expect($tester->mountAction('confirm')->render()->crawler()->filter(Selectors::MOUNTED_MODAL . Selectors::OPEN)->count())->toBe(1);
    })->with([
        'a halt while mounting' => ['haltOnMount'],
        'a cancel while mounting' => ['cancelOnMount'],
        'a disabled action' => ['locked'],
    ]);

    it('unmounts an action that fails validation while mounting, answering the failed validation', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('refuseOnMount');

        expect($tester->response()->getStatusCode())->toBe(422)
            ->and($tester->render()->crawler()->filter(Selectors::MOUNTED_MODAL)->count())->toBe(0);

        $tester->assertActionNotMounted();
    });

    it('drops only the frame it pushed when the name resolves nothing, leaving its parents mounted', function (string $name): void {
        $this->createLiveComponent(ActionStackHost::class)
            ->mountAction('confirm')
            ->call('mountAction', ['name' => $name])
            ->assertActionMounted('confirm')
            ->assertActionNotMounted(['confirm', $name]);
    })->with([
        'a name no action has' => ['nowhere'],
        'a blank name' => [' '],
    ]);
});

describe('unmounting', function (): void {
    it('pops one frame, every frame or up to a named parent, as the caller or the closing action says, dropping the closed frames', function (string $top, bool|string|null $cancelParentActions, array $remaining): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('details')->mountAction($top);
        $topId = $tester->render()->crawler()->filter(Selectors::mountedModal(2))->attr('id');

        $tester->unmountAction($cancelParentActions);

        expect(array_column($tester->component()->mountedActions, 'name'))->toBe($remaining)
            ->and(array_keys($tester->component()->mountedActionsData))->toBe(array_keys($remaining))
            ->and($tester->render()->crawler()->filter(Selectors::MOUNTED_MODAL)->count())->toBe(count($remaining))
            ->and($this->dispatchedBrowserEvents($tester, 'sylament-close-modal'))->toBe([['id' => $topId]]);
    })->with([
        'false pops the top frame' => ['discardAll', false, ['confirm', 'details']],
        'true clears the stack' => ['notes', true, []],
        "a parent's name pops up to it" => ['notes', 'details', ['confirm']],
        'an action cancelling every parent' => ['discardAll', null, []],
        'an action cancelling up to a parent' => ['backToConfirm', null, ['confirm']],
        'an action cancelling no parent' => ['notes', null, ['confirm', 'details']],
    ]);

    it('keeps a parent open when a non-modal child halts on top of it', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)
            ->mountAction('confirm')
            ->mountAction('hold')
            ->assertActionHalted(['confirm', 'hold']);
        $crawler = $tester->render()->crawler();
        $otherHostId = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->render()->crawler()->filter(Selectors::mountedModal(0))->attr('id');

        expect($crawler->filter(Selectors::mountedModal(0) . Selectors::OPEN)->text())->toContain('Confirm the run')
            ->and($crawler->filter(Selectors::mountedModal(1))->count())->toBe(0)
            ->and($otherHostId)->not->toBe($crawler->filter(Selectors::mountedModal(0))->attr('id'));
    });
});

describe('resolving', function (): void {
    it('answers the top frame, an earlier one by index and the whole stack in order', function (): void {
        $host = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('details')->component();
        $parent = $host->getMountedAction(0);

        expect($host->getMountedAction()->getName())->toBe('details')
            ->and($parent->getName())->toBe('confirm')
            ->and($host->getMountedAction(0))->toBe($parent)
            ->and($host->getMountedActions())->toBe([$parent, $host->getMountedAction()]);

        $empty = $this->createLiveComponent(ActionStackHost::class)->component();

        expect($empty->getMountedAction())->toBeNull()
            ->and($empty->getMountedActions())->toBe([]);
    });

    it('finds an action by its factory before a bare method, by a bare method alone, by its own name when it ends in Action, by a factory declaring a union with an action, and once per request', function (): void {
        $host = $this->createLiveComponent(ActionStackHost::class)->component();
        $archive = $host->getAction('archive');

        expect($archive->getLabel())->toBe('Archive by factory')
            ->and($host->getAction('archive'))->toBe($archive)
            ->and($archive->getLiveComponent())->toBe($host)
            ->and($host->getAction('purge')->getLabel())->toBe('Purge')
            ->and($host->getAction('stampAction')->getLabel())->toBe('Stamp by its own name')
            ->and($host->getAction('draft')->getLabel())->toBe('Draft')
            ->and($host->getAction('nowhere'))->toBeNull();
    });

    it('never invokes a method whose return type is not an action, mounting nothing by its name', function (string $name, array $hooks): void {
        $this->createLiveComponent(ActionStackHost::class)
            ->call('mountAction', ['name' => $name])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe($hooks);
    })->with([
        'a method answering a string' => ['status', []],
        'a method declaring no return type' => ['untyped', []],
    ]);

    it('throws for a factory that answers no action at run time, and mounts nothing through it', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class);

        expect(fn () => $tester->component()->getAction('lying'))
            ->toThrow(ActionNotResolvableException::class, 'The [lyingAction] method on the live component returned an instance of [null].');

        $tester->call('mountAction', ['name' => 'lying'])->assertActionNotMounted();
    });

    it('mounts an action whose arguments arrive as a list, handing it the list', function (): void {
        $host = $this->createLiveComponent(ActionStackHost::class)->call('mountAction', ['name' => 'confirm', 'arguments' => ['a', 'b']])->component();

        expect($host->mountedActions)->toBe([['name' => 'confirm', 'arguments' => ['a', 'b'], 'context' => []]])
            ->and($host->getMountedAction()->getArguments())->toBe(['a', 'b']);
    });

    it('throws when a frame names a record key on a host that registered no resolver', function (): void {
        expect(fn () => $this->createLiveComponent(ActionStackHost::class)->call('mountAction', ['name' => 'confirm', 'context' => ['recordKey' => '7']]))
            ->toThrow(LogicException::class, 'An action context supplied a recordKey but the host registered no resolveRecordUsing() callback to resolve it.');
    });

    it('throws when a frame names a schema on a host that is not a schema host', function (): void {
        expect(fn () => $this->createLiveComponent(ActionStackHost::class)->call('mountAction', ['name' => 'ping', 'context' => ['schemaComponent' => 'form']]))
            ->toThrow(LogicException::class, 'is not a schema host — implement Sylament\Schemas\Contracts\InteractsWithSchemas and use the Sylament\Schemas\Concerns\HasSchemas trait.');
    });

    it('throws when an action with a form mounts on a host without schemas, and mounts one without a form', function (): void {
        expect(fn () => $this->createLiveComponent(ActionStackHost::class)->mountAction('rename'))
            ->toThrow(LogicException::class, 'Action [rename] declares a schema but ' . ActionStackHost::class . ' is not a schema host');

        expect($this->createLiveComponent(ActionStackHost::class)->mountAction('ping')->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN)->text())->toContain('Ping');
    });

    it('clears a stack whose record no longer exists on the next request', function (): void {
        $this->seed($product = new Product('Lamp'));

        $tester = $this->createLiveComponent(ProductTable::class)
            ->mountAction(TestAction::make('delete')->table($product))
            ->assertActionMounted(TestAction::make('delete')->table($product));

        $this->entityManager()->remove($this->reload($product));
        $this->entityManager()->flush();

        $tester->refresh()->assertActionNotMounted();
    });
});

describe('a schema context', function (): void {
    it('resolves a schema-component action through the schema it names, never through a host method of another shape', function (string $schemaComponent, array $hooks): void {
        $this->createLiveComponent(SchemaModalHost::class)
            ->call('mountAction', ['name' => 'probe', 'context' => ['schemaComponent' => $schemaComponent]])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe($hooks);
    })->with([
        'a declared schema' => ['form', ['probe: form']],
        'a host method of the contract' => ['unmountAction', []],
        'a parameterless host method' => ['save', []],
        'no method at all' => ['nowhere', []],
    ]);

    it('leaves the record key of a schema-component frame to the component, never asking the host for a resolver', function (): void {
        $this->createLiveComponent(SchemaModalHost::class)
            ->call('mountAction', ['name' => 'probe', 'context' => ['schemaComponent' => 'form', 'recordKey' => '7']])
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['probe: form']);
    });

    it('lets an exception out of a schema builder it calls', function (): void {
        expect(fn () => $this->createLiveComponent(SchemaModalHost::class)->call('mountAction', ['name' => 'probe', 'context' => ['schemaComponent' => 'broken']]))
            ->toThrow(RuntimeException::class, 'The broken schema failed to build.');
    });

    it("resolves a deeper frame's schema context through the schema of the modal under it", function (): void {
        $this->createLiveComponent(SchemaModalHost::class)
            ->mountAction('edit')
            ->mountAction(TestAction::make('count')->schemaComponent()->arguments(['extra' => true]))
            ->assertActionMounted('edit');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['count: [{"extra":true},[]]']);
    });

    it('drops a deeper frame whose schema context names a schema the host builds on demand, leaving its parent mounted', function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction(TestAction::make('peek')->schemaComponent(schema: 'sidebar'));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['peek']);

        $tester->mountAction('edit')
            ->call('mountAction', ['name' => 'peek', 'context' => ['schemaComponent' => 'sidebar']])
            ->assertActionMounted('edit');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });

    it('throws when an action of a modal schema asks for $set, which no component of a root schema answers', function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('edit');

        expect(fn () => $tester->mountAction(TestAction::make('fill')->schemaComponent()))
            ->toThrow(LogicException::class, 'has no schema component: none was resolved from the action itself, its schema container, or its group.');
    });
});

describe('the mounted schema', function (): void {
    it("builds a frame's form as a schema of its own, keyed by its index and bound to its data bag", function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('edit')->setActionData(['title' => 'Lamp']);
        $host = $tester->component();

        expect($host->getMountedActionSchema()->getKey())->toBe('mountedActionSchema0')
            ->and($host->mountedActionHasSchema())->toBeTrue()
            ->and($host->getMountedActionData())->toMatchArray(['title' => 'Lamp'])
            ->and($tester->render()->crawler()->filter(Selectors::mountedModal(0) . ' ' . SchemasSelectors::boundTo('mountedActionsData.0.title'))->count())->toBe(1);

        $tester->assertActionDataSet(['title' => 'Lamp']);

        $bare = $this->createLiveComponent(SchemaModalHost::class)->mountAction('bare')->component();

        expect($bare->mountedActionHasSchema())->toBeFalse()
            ->and($bare->getMountedActionSchema())->toBeNull()
            ->and($this->createLiveComponent(SchemaModalHost::class)->component()->getMountedActionData())->toBe([]);
    });

    it("seeds each frame's form with the dotted chain of mounted names as its operation", function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('edit');
        $edit = $tester->render()->crawler()->filter(Selectors::mountedModal(0));

        expect($edit->filter(SchemasSelectors::boundTo('mountedActionsData.0.shownOnEdit'))->count())->toBe(1)
            ->and($edit->filter(SchemasSelectors::boundTo('mountedActionsData.0.shownOnAttach'))->count())->toBe(0);

        $attach = $tester->mountAction('attachFile')->render()->crawler()->filter(Selectors::mountedModal(1));

        expect($attach->filter(SchemasSelectors::boundTo('mountedActionsData.1.shownOnAttach'))->count())->toBe(1)
            ->and($attach->filter(SchemasSelectors::boundTo('mountedActionsData.1.shownOnEdit'))->count())->toBe(0);
    });

    it('seeds a data bag slot per mount and purges every slot the stack no longer has, by index whatever the order', function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('edit');

        expect(array_keys($tester->component()->mountedActionsData))->toBe([0]);

        $tester->setActionData(['title' => 'Lamp'])->mountAction('attachFile');

        expect(array_keys($tester->component()->mountedActionsData))->toBe([0, 1]);

        $tester->set('mountedActionsData', [5 => [], 1 => ['file' => 'a.txt'], 0 => ['title' => 'Lamp']])->unmountAction();

        expect($tester->component()->mountedActionsData)->toBe([0 => ['title' => 'Lamp']]);

        $tester->unmountAction();

        expect($tester->component()->mountedActionsData)->toBe([]);
    });

    it("keeps a parent modal's errors while a child opens above it, and forgets a frame's errors with the frame", function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)
            ->mountAction('edit')
            ->callMountedAction()
            ->assertHasActionErrors(['title'])
            ->mountAction('attachFile');

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . ' ' . SchemasSelectors::boundTo('mountedActionsData.0.title') . '[aria-invalid="true"]')->count())->toBe(1);

        $tester->unmountAction(true)->mountAction('edit');

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . ' ' . SchemasSelectors::boundTo('mountedActionsData.0.title') . '[aria-invalid="true"]')->count())->toBe(0);
    });

    it("keeps a failed modal's errors through an unrelated live update, and drops them once the field is fixed", function (): void {
        $this->createLiveComponent(SchemaModalHost::class)
            ->mountAction('edit')
            ->callMountedAction()
            ->assertHasActionErrors(['title'])
            ->set('data.outside', 'Shelf')
            ->assertHasActionErrors(['title'])
            ->setActionData(['title' => 'Lamp'])
            ->assertHasNoActionErrors();
    });

    it("checks a field of an action that names no record class against the record class the host answers for its modal schemas", function (): void {
        $this->seed(new Product('Lamp'));

        $this->createLiveComponent(SchemaRecordHost::class)
            ->mountAction('register')
            ->setActionData(['name' => 'Lamp'])
            ->callMountedAction()
            ->assertHasActionErrors(['name'])
            ->setActionData(['name' => 'Desk'])
            ->callMountedAction()
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['register: {"name":"Desk"}']);
    });

    it("replaces the whole stack from an action's own call, forgetting the old frame's data and form", function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)
            ->mountAction('swap')
            ->setActionData(['code' => 'X1'])
            ->callMountedAction()
            ->assertActionMounted('bare');
        $bare = $tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

        expect($tester->component()->mountedActionsData)->toBe([0 => []])
            ->and($bare->text())->toContain('Bare')
            ->and($bare->filter(SchemasSelectors::boundTo('mountedActionsData.0.code'))->count())->toBe(0);

        $tester->callMountedAction()->assertActionMounted('swap');

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN . ' ' . SchemasSelectors::boundTo('mountedActionsData.0.code'))->count())->toBe(1);
    });
});

describe('calling', function (): void {
    it('runs the hooks, the body, afterActionCalled(), the notification and the redirect in that order', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->callAction('ordered');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['before', 'action', 'after', 'afterActionCalled: ordered', 'notification', 'redirect']);

        $tester->assertRedirect('/ordered')->assertNotified('Ordered');
    });

    it('runs the form arm before the body, with the modal schema as both schema and form, and nothing past a failed validation', function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('edit')->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeFormValidated']);

        $tester->assertHasActionErrors(['title'])->assertActionMounted('edit')->setActionData(['title' => 'Lamp'])->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([
            'beforeFormValidated',
            'afterFormValidated',
            'before',
            'action: mountedActionSchema0 as form {"title":"Lamp","shownOnEdit":null}',
            'after',
        ]);

        $tester->assertActionNotMounted();
    });

    it("calls an action without a form with the schema it was registered on, or none", function (): void {
        $this->createLiveComponent(SchemaModalHost::class)->mountAction(TestAction::make('probe')->schemaComponent());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['probe: form']);

        $this->createLiveComponent(ActionStackHost::class)->callAction('ping');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['ping: no schema', 'afterActionCalled: ping']);
    });

    it("validates only the mounted action's form, whatever the host's own form holds", function (): void {
        $this->createLiveComponent(SchemaModalHost::class)
            ->callAction(TestAction::make('annotate')->schemaComponent(), data: ['remark' => 'Fragile'])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['annotate: {"remark":"Fragile"}']);
    });

    it('unmounts a modal-less action whose form fails, answering the failed validation', function (): void {
        $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('quickRename');

        expect($tester->response()->getStatusCode())->toBe(422)
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        $tester->assertActionNotMounted();
    });

    it('commits the work before a halt or a cancel unless it asks for a rollback, keeping a halted action mounted', function (array $arguments, int $rows, string $marker, bool $mounted): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->callAction('tally', arguments: $arguments);

        expect($this->statementsRun())->toContain('"START TRANSACTION"', $marker)
            ->and($this->entityManager()->getRepository(Tally::class)->count([]))->toBe($rows);

        $mounted ? $tester->assertActionHalted('tally') : $tester->assertActionNotMounted();
    })->with([
        'nothing stops it' => [[], 1, '"COMMIT"', false],
        'a halt' => [['outcome' => 'halt'], 1, '"COMMIT"', true],
        'a halt rolling back' => [['outcome' => 'halt', 'rollBack' => true], 0, '"ROLLBACK"', true],
        'a cancel' => [['outcome' => 'cancel'], 1, '"COMMIT"', false],
        'a cancel rolling back' => [['outcome' => 'cancel', 'rollBack' => true], 0, '"ROLLBACK"', false],
    ]);

    it('rolls back and rethrows what the body throws', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('tally', ['outcome' => 'throw']);

        expect(fn () => $tester->callMountedAction())->toThrow(RuntimeException::class, 'The tally failed.')
            ->and($this->statementsRun())->toContain('"START TRANSACTION"', '"ROLLBACK"')
            ->and($this->entityManager()->getRepository(Tally::class)->count([]))->toBe(0);
    });

    it('runs nothing for a call while no action is mounted', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->call('callMountedAction');

        expect($tester->response()->getStatusCode())->toBe(200)
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        $tester->assertActionNotMounted();
    });

    it('redirects from a modal-less action run at mount', function (): void {
        $this->createLiveComponent(ActionStackHost::class)->mountAction('leave')->assertRedirect('/left');
    });

    it('redirects from an action that replaced the stack during its call', function (): void {
        $this->createLiveComponent(SchemaModalHost::class)->callAction('relocate')->assertRedirect('/relocated');
    });

    it('skips the body of an action its call arguments disable, keeping it mounted until a call that leaves it enabled', function (): void {
        $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('guarded')->callMountedAction(['locked' => true]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

        $tester->assertActionMounted('guarded')->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['guarded', 'afterActionCalled: guarded']);

        $tester->assertActionNotMounted();
    });
});

describe('records', function (): void {
    it("resolves a root frame's key to the record its heading, its form and its body see, through the one callback", function (string $name, array $context): void {
        $this->seed($atelier = new Atelier('Loft'));

        $tester = $this->createLiveComponent(AtelierResolvingHost::class)->mountAction(['name' => $name, 'context' => [...$context, 'recordKey' => (string) $atelier->id]]);

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN)->text())->toContain("Inspect atelier {$atelier->id}");

        expect($tester->component()->getMountedActionSchema()->getRecord())->toBeInstanceOf(Atelier::class)->toBeRecord(id: $atelier->id);

        $tester->assertActionDataSet(['name' => 'Loft'])->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["inspect: {$atelier->id}"]);
    })->with([
        'a host action' => ['inspect', []],
        'a table context on a host that is not a table' => ['inspect', ['table' => true]],
    ]);

    it('binds a resolved record whose name prints blank', function (): void {
        $this->seed($atelier = new Atelier());

        $tester = $this->createLiveComponent(AtelierResolvingHost::class)->mountAction(['name' => 'inspect', 'context' => ['recordKey' => (string) $atelier->id]]);

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN)->text())->toContain("Inspect atelier {$atelier->id}");
    });

    it('keeps the record a child frame inherited from its parent when the child is called', function (): void {
        $this->seed($atelier = new Atelier('Loft'));

        $tester = $this->createLiveComponent(AtelierResolvingHost::class)
            ->mountAction(['name' => 'inspect', 'context' => ['recordKey' => (string) $atelier->id]])
            ->mountAction('stamp');

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(1) . Selectors::OPEN)->text())->toContain("Stamp atelier {$atelier->id}");

        $tester->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["stamp: {$atelier->id}"]);
    });

    it('clears the record of a create-shaped action once the stack empties or the action replaced itself, never under an open parent', function (): void {
        $this->seed($atelier = new Atelier('Loft'));
        $key = ['recordKey' => (string) $atelier->id];

        $host = $this->createLiveComponent(AtelierResolvingHost::class)->mountAction(['name' => 'inspect', 'context' => $key])->mountAction('copy')->component();
        $copy = $host->getMountedAction();
        $host->unmountAction();

        expect($copy->getRecord(withDefault: false))->toBeInstanceOf(Atelier::class)->toBeRecord(id: $atelier->id);

        $host = $this->createLiveComponent(AtelierResolvingHost::class)->mountAction(['name' => 'draft', 'context' => $key])->component();
        $draft = $host->getMountedAction();

        expect($draft->getRecord(withDefault: false))->toBeInstanceOf(Atelier::class);

        $host->unmountAction();

        expect($draft->getRecord(withDefault: false))->toBeNull();

        $host = $this->createLiveComponent(AtelierResolvingHost::class)->mountAction(['name' => 'draft', 'context' => $key])->component();
        $draft = $host->getMountedAction();
        $host->callMountedAction();

        expect($draft->getRecord(withDefault: false))->toBeNull()
            ->and($host->getMountedAction()->getName())->toBe('inspect');
    });
});

describe('a table host', function (): void {
    it("binds a grouped row action's record to its group, without asking the host's own callback", function (): void {
        $this->seed($product = new Product('Lamp'));

        $tester = $this->createLiveComponent(StockedProductTable::class)->mountAction(TestAction::make('rename')->table($product));

        expect($tester->component()->getMountedAction()->getRootGroup()->getRecord())->toBeInstanceOf(Product::class)->toBeRecord(name: 'Lamp');

        $tester->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['rename: Lamp']);
    });

    it('mounts nothing for a record key the table does not hold or that is not a scalar', function (mixed $recordKey): void {
        $this->seed($line = new OrderLine(order: 7, line: 2));

        $this->createLiveComponent(OrderLineTable::class)
            ->call('mountAction', ['name' => 'delete', 'context' => ['table' => true, 'recordKey' => $recordKey]])
            ->assertActionNotMounted()
            ->mountAction(TestAction::make('delete')->table($line))
            ->assertActionMounted(TestAction::make('delete')->table($line));
    })->with([
        'a key no row has' => ['9~9'],
        'a list' => [['7', '2']],
    ]);

    it('resolves a bulk name through the bulk actions, and nothing for a name neither list holds', function (): void {
        $this->createLiveComponent(DeletingProductTable::class)
            ->call('mountAction', ['name' => 'nowhere', 'context' => ['table' => true, 'bulk' => true]])
            ->assertActionNotMounted()
            ->mountAction(TestAction::make('handBuilt')->table()->bulk())
            ->assertActionMounted(TestAction::make('handBuilt')->table()->bulk());
    });

    it("shows a row a mounted action wrote, whatever way the run ends, though the run read the rows first", function (array $arguments): void {
        $this->seed(new Product('Lamp'));

        $tester = $this->createLiveComponent(StockedProductTable::class)->callAction(TestAction::make('restock')->table(), arguments: $arguments);

        expect($tester->render()->crawler()->filter(TablesSelectors::TEXT_COLUMN)->each(static fn ($cell): string => $cell->text()))->toBe(['Lamp', 'Restocked']);
    })->with([
        'a success' => [[]],
        'a failure' => [['outcome' => 'failure']],
        'a halt' => [['outcome' => 'halt']],
        'a cancel' => [['outcome' => 'cancel']],
    ]);
});

describe('host defaults', function (): void {
    it('links only the action its URL hook answers for, unless the action opens a modal or has a URL of its own', function (bool $answersEveryAction, string $deleteSelector): void {
        $crawler = $this->createLiveComponent(MemoDefaultsHost::class, ['answersEveryAction' => $answersEveryAction])->render()->crawler();

        expect($crawler->filter('a[href="' . ($answersEveryAction ? MemoDefaultsHost::EVERY_ACTION_URL : MemoDefaultsHost::EDIT_URL) . '"]')->text())->toContain('Edit')
            ->and($crawler->filter($deleteSelector)->reduce(static fn ($trigger): bool => str_contains($trigger->text(), 'Delete'))->count())->toBe(1)
            ->and($crawler->filter('button')->reduce(static fn ($button): bool => str_contains($button->text(), 'Review'))->count())->toBe(1)
            ->and($crawler->filter('a[href="/visit"]')->text())->toContain('Visit');
    })->with([
        'a hook answering for its edit action' => [false, 'button[data-action]'],
        'a hook answering for every action' => [true, 'a[href="' . MemoDefaultsHost::EVERY_ACTION_URL . '"]'],
    ]);

    it('redirects after a run to the URL its hooks answer for its success or its failure', function (string $name, string $url): void {
        $this->createLiveComponent(MemoDefaultsHost::class)->callAction($name)->assertRedirect($url);
    })->with([
        'a success' => ['archive', '/memos/archived'],
        'a failure' => ['restore', '/memos/not-restored'],
    ]);

    it('hands an action that declares none the record, title, form, record class and label its hooks answer', function (): void {
        $tester = $this->createLiveComponent(MemoDefaultsHost::class);

        $triggers = $tester->render()->crawler()->filter('button');

        expect($triggers->reduce(static fn ($button): bool => str_contains($button->text(), 'New memo entry'))->count())->toBe(1)
            ->and($triggers->reduce(static fn ($button): bool => mb_trim($button->text()) === 'New memo')->count())->toBe(1);

        $tester->mountAction('view')->assertActionDataSet(['subject' => 'Ledger']);

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN)->text())->toContain('View the ledger');
    });
});

describe('modals', function (): void {
    it('opens a modal for an action whose only content is its form, and answers that nothing opens while nothing is mounted', function (): void {
        $tester = $this->createLiveComponent(SchemaDeclarationHost::class);

        expect($tester->component()->mountedActionShouldOpenModal())->toBeFalse()
            ->and($tester->render()->crawler()->filter(Selectors::MOUNTED_MODAL)->count())->toBe(0);

        $tester->mountAction('listed')->assertActionMounted('listed');

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN . ' ' . SchemasSelectors::boundTo('mountedActionsData.0.title'))->count())->toBe(1)
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });
});
