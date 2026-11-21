<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Support\ArrayRecord;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Enums\Size;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\AddressStateForm;
use Sylament\Tests\Fixtures\Actions\LocalisedSectionHost;
use Sylament\Tests\Fixtures\Actions\MountedActionsHost;
use Sylament\Tests\Fixtures\Actions\RecordDefaultsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Actions\TranslatedRecordHost;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LocalisedLeaflet;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\ParcelWaybill;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\SkuParcel;
use Sylament\Tests\Fixtures\Forms\RepeaterHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Icons\SizedIcon;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\InjectingProductTable;
use Sylament\Tests\Fixtures\Tables\InvoiceLineTable;
use Sylament\Tests\Fixtures\Tables\OrderLineTable;
use Symfony\Component\DomCrawler\Crawler;

describe('trigger rendering', function (): void {
    it('renders each look through its support primitive, a button unless told otherwise, marked as an action', function (Closure $action, string $look): void {
        expect($this->renderViewComponent($action()))->toContainButtons(Selectors::TRIGGER . $look, ['Save']);
    })->with([
        'a button by default' => [fn (): Action => Action::make('save'), SupportSelectors::BUTTON],
        'a badge' => [fn (): Action => Action::make('save')->badge(), SupportSelectors::BADGE],
        'a dropdown item' => [fn (): Action => Action::make('save')->grouped(), SupportSelectors::DROPDOWN_ITEM],
        'an icon button' => [fn (): Action => Action::make('save')->iconButton(), SupportSelectors::ICON_BUTTON],
        'a link' => [fn (): Action => Action::make('save')->link(), SupportSelectors::LINK],
    ]);

    it('keeps the look it was given inside a group, which turns its other actions into dropdown items', function (): void {
        $render = $this->renderViewComponent(ActionGroup::make([Action::make('save')->link(), Action::make('drop')]));

        expect($render)->toContainElement(Selectors::TRIGGER . SupportSelectors::LINK, 'Save')
            ->toContainElement(Selectors::TRIGGER . SupportSelectors::DROPDOWN_ITEM, 'Drop');
    });

    it('draws its icon after its label on a link when positioned after', function (): void {
        expect($this->renderViewComponent(Action::make('save')->link()->icon('support:star')->iconPosition(IconPosition::After)))
            ->toContainElement(SupportSelectors::LINK, 'SaveStar');
    });

    it('renders an uncoloured action in primary', function (Closure $action, string $look): void {
        expect($this->renderViewComponent($action()))->toContainButtons($look . SupportSelectors::COLORED . SupportSelectors::color('primary'), ['Save']);
    })->with([
        'a badge' => [fn (): Action => Action::make('save')->badge(), SupportSelectors::BADGE],
        'a button' => [fn (): Action => Action::make('save'), SupportSelectors::BUTTON],
        'an icon button' => [fn (): Action => Action::make('save')->iconButton(), SupportSelectors::ICON_BUTTON],
        'a link' => [fn (): Action => Action::make('save')->link(), SupportSelectors::LINK],
    ]);

    it('renders an uncoloured dropdown item gray', function (): void {
        expect($this->renderViewComponent(Action::make('save')->grouped()))->toContainElement(SupportSelectors::DROPDOWN_ITEM . SupportSelectors::GRAY, 'Save')
            ->and($this->renderViewComponent(Action::make('save')->grouped()->color('danger')))->toContainElement(SupportSelectors::DROPDOWN_ITEM . SupportSelectors::color('danger'), 'Save');
    });

    it("titles its trigger with its tooltip, over an icon button's own label", function (): void {
        expect($this->renderViewComponent(Action::make('save')->tooltip('Keeps a copy')))
            ->toContainElement(SupportSelectors::BUTTON . '[title="Keeps a copy"]', 'Save')
            ->and($this->renderViewComponent(Action::make('save')->iconButton()))
            ->toContainElement(SupportSelectors::ICON_BUTTON . '[aria-label="Save"][title="Save"]')
            ->and($this->renderViewComponent(Action::make('save')->iconButton()->tooltip('Keeps a copy')))
            ->toContainElement(SupportSelectors::ICON_BUTTON . '[aria-label="Save"][title="Keeps a copy"]');
    });

    it("renders its extra attributes on an icon button, its classes beside the trigger's own", function (): void {
        expect($this->renderViewComponent(Action::make('save')->iconButton()->extraAttributes(['class' => 'custom-hook', 'data-x' => '1'])))
            ->toContainElement(SupportSelectors::ICON_BUTTON . Selectors::TRIGGER . '.custom-hook[data-x="1"][aria-label="Save"]');
    });

    it("dispatches its event on click even inside a parent's modal", function (Closure $action, string $look): void {
        $render = $this->renderViewComponent($action());

        expect($render)->toContainElement($look . Selectors::dispatchedEvent('saved'))
            ->and($render->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($this->renderViewComponent($action()->dispatch(null)))
            ->toContainElement($look . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'callMountedAction'));
    })->with([
        'a button' => [fn (): Action => Action::make('notify')->dispatch('saved')->callParent('callMountedAction'), SupportSelectors::BUTTON],
        'an icon button' => [fn (): Action => Action::make('notify')->iconButton()->dispatch('saved')->callParent('callMountedAction'), SupportSelectors::ICON_BUTTON],
    ]);

    it('submits a form with no click binding of its own, keeping its dismiss when it closes', function (): void {
        $submit = $this->renderViewComponent(Action::make('submit')->submit('callMountedAction'));
        $closingSubmit = $this->renderViewComponent(Action::make('submit')->submit('callMountedAction')->close());

        expect($submit)->toContainElement('button[type="submit"]', 'Submit')
            ->and($submit->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($closingSubmit)->toContainElement('button[type="submit"]' . SupportSelectors::MODAL_DISMISS, 'Submit')
            ->and($closingSubmit->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('submit')))->toContainElement('button[type="button"]' . Selectors::LIVE_CLICK, 'Submit');
    });

    it('dismisses its modal from a closing icon button, unless it links away or is disabled', function (): void {
        expect($this->renderViewComponent(Action::make('cancel')->iconButton()->close()))
            ->toContainElement(SupportSelectors::ICON_BUTTON . SupportSelectors::MODAL_DISMISS)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->url('/back'))->filter(SupportSelectors::MODAL_DISMISS))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->url('/back')))->toContainElement('a[href="/back"]' . SupportSelectors::ICON_BUTTON)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->disabled())->filter(SupportSelectors::MODAL_DISMISS))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->disabled()))->toContainElement('button[disabled]' . SupportSelectors::ICON_BUTTON);
    });
});

describe('serialization', function (): void {
    it('restores the trigger it serialized, without the behaviour behind it', function (): void {
        $payload = Action::make('notify')
            ->label('Notify')
            ->badge()
            ->color('danger')
            ->size(Size::Small)
            ->icon('support:star')
            ->iconPosition(IconPosition::After)
            ->tooltip('Tells the team')
            ->url('/inbox', shouldOpenInNewTab: true)
            ->extraAttributes(['data-x' => '1'])
            ->markAsRead()
            ->action(static fn (): string => 'ran')
            ->toArray();

        $restored = Action::fromArray($payload);

        expect($restored->toArray())->toBe($payload)
            ->and($restored->call())->toBeNull()
            ->and($this->renderViewComponent($restored))->toContainElement(
                'a[href="/inbox"][target="_blank"][title="Tells the team"][data-x="1"]' . SupportSelectors::BADGE . SupportSelectors::color('danger') . SupportSelectors::size('sm'),
                'NotifyStar',
            );
    });

    it('writes its icon position and sizes as scalars, and keeps a scalable icon to resolve at the size it renders', function (): void {
        $payload = Action::make('star')->icon(SizedIcon::Star)->iconSize(IconSize::Large)->iconPosition(IconPosition::After)->size(Size::Small)->toArray();

        expect($payload)->toMatchArray(['icon' => SizedIcon::Star, 'iconPosition' => 'after', 'iconSize' => 'lg', 'size' => 'sm'])
            ->and($this->renderViewComponent(Action::fromArray($payload)))
            ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::size('sm'), 'StarStar, large');
    });

    it('restores only its own templates, a button for any other', function (): void {
        expect($this->renderViewComponent(Action::fromArray(['name' => 'save', 'template' => '@Evil/anything.html.twig'])))
            ->toContainElement(SupportSelectors::BUTTON, 'Save')
            ->and($this->renderViewComponent(Action::fromArray(['name' => 'save', 'template' => Action::GROUPED_TEMPLATE])))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM, 'Save');
    });

    it('dispatches a restored event to the component it names, and plainly when it names none', function (): void {
        $untargeted = $this->renderViewComponent(Action::fromArray(['name' => 'notify', 'event' => 'saved', 'dispatchDirection' => 'to']));

        expect($untargeted)->toContainElement(Selectors::dispatchedEvent('saved'), 'Notify')
            ->and($untargeted->filter(Selectors::dispatchedTo()))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::fromArray(['name' => 'notify', 'event' => 'saved', 'dispatchDirection' => 'to', 'dispatchToComponent' => 'Inbox'])))
            ->toContainElement(Selectors::dispatchedEvent('saved') . Selectors::dispatchedTo('Inbox'), 'Notify');
    });
});

describe('call()', function (): void {
    it('runs its action with the parameters it is called with and answers what it returns', function (): void {
        expect(Action::make('greet')->action(static fn (string $tone): string => "said {$tone}")->call(['tone' => 'loudly']))->toBe('said loudly');
    });
});

describe('closure injection', function (): void {
    it('hands a table row action its table by name and by type, its record and its host', function (): void {
        $this->seed($lamp = new Product('Lamp'), new Product('Vase'));

        $this->createLiveComponent(InjectingProductTable::class)->callAction(TestAction::make('inspect')->table($lamp));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['inspect Lamp, its table bound, its host bound']);
    });

    it('hands an action outside a table no table, by name or by type', function (): void {
        expect(Action::make('save')->evaluate(static fn (?Table $table, ?Table $grid): array => [$table, $grid]))->toBe([null, null]);
    });

    it("hands a table's header action the table's record class before any record exists", function (): void {
        $this->createLiveComponent(InjectingProductTable::class)->callAction(TestAction::make('stock')->table());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['stock ' . Product::class . ' null']);
    });

    it('hands a bulk action the selected records by name and their query by name and by type', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), new Product('Rug'));

        $this->createLiveComponent(InjectingProductTable::class)
            ->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('tally')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['tally Lamp and Vase, queried as 2 and 2']);
    });

    it('resolves the selected-record names as any other parameter unless the action reads the selection, a flag given as a closure included', function (): void {
        expect(Action::make('save')->evaluate(static fn (?array $records = ['own']): ?array => $records))->toBe(['own'])
            ->and(fn (): mixed => Action::make('save')->accessSelectedRecords(static fn (): bool => false)->evaluate(static fn (?array $records): ?array => $records))
            ->toThrow(LogicException::class);
    });

    it("hands a schema action its state, its schema's state, its schema, and a field it reads and writes by name", function (): void {
        $tester = $this->createLiveComponent(AddressStateForm::class, ['data' => ['address' => ['street' => 'Main'], 'note' => 'hi']])
            ->callAction(TestAction::make('peek')->schemaComponent('address'));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([json_encode([
            'state' => ['street' => 'Main'],
            'schemaState' => ['address' => ['street' => 'Main'], 'note' => 'hi first:get'],
            'schema' => 'form.address',
            'get' => 'Main',
        ], JSON_THROW_ON_ERROR)]);

        expect($tester->component()->data['address'])->toBe(['street' => 'Side Street']);
    });

    it("hands a component's action the component, its own state, and its fields through their casts by type, whatever the parameters are called", function (): void {
        $tester = $this->createLiveComponent(AddressStateForm::class, ['data' => ['address' => ['street' => 'Main'], 'note' => 'hi']])
            ->callAction(TestAction::make('stamp')->schemaComponent());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([json_encode([
            'state' => ['address' => ['street' => 'Main'], 'note' => 'hi'],
            'schemaComponent' => Section::class,
            'read' => 'hi first:get',
        ], JSON_THROW_ON_ERROR)]);

        expect($tester->component()->data['note'])->toBe('stamped first:set');
    });

    it("hands an extra item action the state of the item it was mounted on, an item keyed by a number included", function (): void {
        $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]])
            ->callAction(TestAction::make('inspect')->schemaComponent('lines'), arguments: ['item' => 1]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['inspect {"name":"Grace"} on lines']);
    });

    it('refuses $get and $set, by name or by type, to an action outside a schema', function (Closure $closure): void {
        expect(fn (): mixed => Action::make('save')->evaluate($closure))
            ->toThrow(LogicException::class, Action::class);
    })->with([
        '$get' => [static fn ($get): mixed => $get],
        '$set' => [static fn ($set): mixed => $set],
        'a Get' => [static fn (Get $lookup): Get => $lookup],
        'a Set' => [static fn (Set $write): Set => $write],
    ]);
});

describe('record chain', function (): void {
    it('reaches its own record, else the first its group, its schema, its component or its host has', function (Action $action, Product $record): void {
        expect($action->evaluate(static fn (mixed $record, ?string $recordClass): array => [$record, $recordClass]))->toEqual([$record, Product::class]);
    })->with([
        'its own' => fn (): array => [Action::make('edit')->record($lamp = new Product('Own lamp')), $lamp],
        "its group's" => function (): array {
            ActionGroup::make([$action = Action::make('edit')])->record($lamp = new Product('Group lamp'));

            return [$action, $lamp];
        },
        "its group's before its schema's" => function (): array {
            ActionGroup::make([$action = Action::make('edit')->schemaContainer(new MountedActionsHost()->makeSchema()->record(new Product('Schema lamp')))])->record($lamp = new Product('Group lamp'));

            return [$action, $lamp];
        },
        "its schema's" => fn (): array => [Action::make('edit')->schemaContainer(new MountedActionsHost()->makeSchema()->record($lamp = new Product('Schema lamp'))), $lamp],
        "its schema's before its component's" => function (): array {
            [$component] = new MountedActionsHost()->makeSchema()->record(new Product('Component lamp'))->components([TextInput::make('name')])->getComponents();

            return [Action::make('edit')->schemaContainer(new MountedActionsHost()->makeSchema()->record($lamp = new Product('Schema lamp')))->schemaComponent($component), $lamp];
        },
        "its component's" => function (): array {
            [$component] = new MountedActionsHost()->makeSchema()->record($lamp = new Product('Component lamp'))->components([TextInput::make('name')])->getComponents();

            return [Action::make('edit')->schemaComponent($component), $lamp];
        },
        "its host's" => function (): array {
            $action = new RecordDefaultsHost()->getAction('defaulted') ?? throw new LogicException('The host has no [defaulted] action.');

            return [$action, $action->getLiveComponent()->getDefaultActionRecord($action)];
        },
    ]);

    it('reaches its own record class over its record, else the one its group, its schema or its component names before any record exists', function (Action $action): void {
        expect($action->evaluate(static fn (mixed $record, ?string $recordClass): array => [$record, $recordClass]))->toBe([null, Product::class]);
    })->with([
        'its own, over a record of another class' => fn (): Action => Action::make('create')->recordClass(Product::class)->record(new Article('Notes')),
        "its group's" => function (): Action {
            ActionGroup::make([$action = Action::make('create')])->recordClass(Product::class);

            return $action;
        },
        "its schema's" => fn (): Action => Action::make('create')->schemaContainer(new MountedActionsHost()->makeSchema()->record(Product::class)),
        "its component's" => function (): Action {
            [$component] = new MountedActionsHost()->makeSchema()->record(Product::class)->components([TextInput::make('name')])->getComponents();

            return Action::make('create')->schemaComponent($component);
        },
    ]);

    it('stops at its own record even when it is an empty object or a key, and answers none for one that is not of its record class', function (): void {
        ActionGroup::make([
            $empty = Action::make('edit')->record(new ArrayObject()),
            $key = Action::make('pick')->record('7'),
            $foreign = Action::make('view')->recordClass(Product::class)->record(new stdClass()),
            $plain = Action::make('copy')->recordClass(Product::class),
        ])->record($lamp = new Product('Lamp'));
        $record = static fn (mixed $record): mixed => $record;

        expect($empty->evaluate($record))->toEqual(new ArrayObject())
            ->and($key->evaluate($record))->toBe('7')
            ->and($foreign->evaluate($record))->toBeNull()
            ->and($plain->evaluate($record))->toBe($lamp);
    });
});

describe('record labels', function (): void {
    it("titles a table row's record through its table", function (): void {
        $this->seed($lamp = new Product('Lamp'), new Product('Vase'));

        expect($this->createLiveComponent(InjectingProductTable::class)->mountAction(TestAction::make('delete')->table($lamp))->render()->crawler())
            ->toContainElement(SupportSelectors::MODAL_HEADING, 'Delete Lamp');
    });

    it("titles a record outside a table by its host's title, else by its host's label for the host's own record class, else by the class", function (string $action, string $heading): void {
        expect($this->createLiveComponent(RecordDefaultsHost::class)->mountAction($action)->render()->crawler())
            ->toContainElement(SupportSelectors::MODAL_HEADING, $heading);
    })->with([
        "the host's title" => ['titled', 'Delete The lamp'],
        "the host's label" => ['labelled', 'Delete stock item'],
        "the class's label, the host answering none" => ['unlabelled', 'Delete product'],
        "no title, the host answering an empty one" => ['untitled', 'Delete'],
    ]);

    it('translates the title or the label its host answers as a message before it composes the heading', function (string $action, string $heading): void {
        expect($this->createLiveComponent(TranslatedRecordHost::class)->mountAction($action)->render()->crawler())
            ->toContainElement(SupportSelectors::MODAL_HEADING, $heading);
    })->with([
        "the host's title" => ['titled', 'Delete The crate'],
        "the host's label" => ['labelled', 'Delete crate'],
    ]);
});

describe('context', function (): void {
    it('puts its record key on the wire only where one scalar field is the whole identity, whatever it is called', function (Closure $record, ?array $context): void {
        $trigger = $this->renderViewComponent(Action::make('edit')->record($record()))->filter(Selectors::TRIGGER);

        expect($trigger->attr('data-live-context-param'))->toBe($context === null ? null : json_encode($context, JSON_THROW_ON_ERROR))
            ->and($trigger->attr('data-live-name-param'))->toBe('edit');
    })->with([
        'a generated id' => [fn (): Closure => static function (): Product {
            $lamp = new Product('Lamp');
            $lamp->id = 5;

            return $lamp;
        }, ['recordKey' => '5']],
        'a key not called id' => [fn (): Closure => static fn (): SkuParcel => new SkuParcel('AB-1'), ['recordKey' => 'AB-1']],
        'a key of two scalars, one called id' => [fn (): Closure => static fn (): LocalisedLeaflet => new LocalisedLeaflet(5, 'en'), null],
        'a key that is an association' => [fn (): Closure => static fn (): ParcelWaybill => new ParcelWaybill(new SkuParcel('AB-1')), null],
        'an unmapped class' => [fn (): Closure => static fn (): object => new class {
            public int $id = 7;
        }, ['recordKey' => '7']],
        'an array record' => [fn (): Closure => static fn (): array => [ArrayRecord::getKeyName() => 'k1'], ['recordKey' => 'k1']],
    ]);

    it('puts its schema key on the wire beside its record key, where a modal action it declares carries only the record key', function (): void {
        $lamp = new Product('Lamp');
        $lamp->id = 5;
        [$component] = new MountedActionsHost()->makeSchema()->key('form')->components([TextInput::make('code')])->getComponents();
        $outer = Action::make('outer')->schemaComponent($component)->record($lamp)->registerModalActions([Action::make('inner')]);
        $context = fn (Action $action): ?string => $this->renderViewComponent($action)->filter(Selectors::TRIGGER)->attr('data-live-context-param');

        expect($context($outer))->toBe('{"recordKey":"5","schemaComponent":"form.code"}')
            ->and($context($outer->getModalAction('inner') ?? throw new LogicException('The outer action declares no [inner] action.')))->toBe('{"recordKey":"5"}');
    });

    it('never puts a record its host supplied by default on the wire', function (): void {
        $action = new RecordDefaultsHost()->getAction('defaulted') ?? throw new LogicException('The host has no [defaulted] action.');

        expect($action->evaluate(static fn (?Product $record): ?string => $record?->name))->toBe('Lamp')
            ->and($this->renderViewComponent($action)->filter(Selectors::TRIGGER)->attr('data-live-context-param'))->toBeNull();
    });

    it('flags a bulk action as one only on a table', function (): void {
        $this->seed(new Product('Lamp'));

        expect($this->createLiveComponent(InjectingProductTable::class)->render()->crawler()->filter(Selectors::liveParameter('name', 'tally'))->attr('data-live-context-param'))
            ->toBe('{"table":true,"bulk":true}')
            ->and($this->renderViewComponent(Action::make('tally')->bulk())->filter(Selectors::TRIGGER)->attr('data-live-context-param'))->toBeNull();
    });

    it("mounts a table row's action from the key its trigger carries, the table's own for a composite key", function (string $table, object $line, object $sameHead): void {
        $this->seed($line, $sameHead);
        $tester = $this->createLiveComponent($table);
        $key = $tester->component()->getTableRecordKey($line);

        $contexts = $tester->render()->crawler()->filter(Selectors::liveParameter('name', 'delete'))->each(static fn (Crawler $trigger): ?string => $trigger->attr('data-live-context-param'));

        expect($contexts)->toContain(json_encode(['recordKey' => $key, 'table' => true], JSON_THROW_ON_ERROR));

        $tester->call('mountAction', ['name' => 'delete', 'context' => ['recordKey' => $key, 'table' => true]])
            ->callMountedAction()
            ->assertCanSeeTableRecords([$sameHead])
            ->assertCanNotSeeTableRecords([$line]);
    })->with([
        'scalar fields' => fn (): array => [OrderLineTable::class, new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3)],
        'an association plus a scalar' => fn (): array => [InvoiceLineTable::class, new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3)],
    ]);
});

describe('a localised live route', function (): void {
    it('keeps a section-registered action mounted across a live call, the section keyed by a heading the locale translates', function (): void {
        $tester = $this->createLiveComponent(LocalisedSectionHost::class)->setRouteLocale('sk');

        $tester->call('mountAction', ['name' => 'stamp', 'context' => ['schemaComponent' => 'form.zasielky::section'], 'arguments' => []]);
        $tester->call('callMountedAction', ['arguments' => []]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['stamped']);
    });
});
