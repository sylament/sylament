<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\BulkActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Enums\Size;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Icons\Icon;
use Sylament\Tests\Fixtures\Support\Icons\SizedIcon;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

use function Symfony\Component\Translation\t;

describe('construction', function (): void {
    it("lists its members in order and maps every leaf action by name, a nested group's leaves included", function (): void {
        $group = ActionGroup::make([
            $edit = Action::make('edit'),
            $more = ActionGroup::make([$view = Action::make('view'), ActionGroup::make([$delete = Action::make('delete')])]),
            $copy = Action::make('copy'),
        ]);

        expect($group->getActions())->toBe([$edit, $more, $copy])
            ->and($group->getFlatActions())->toBe(['edit' => $edit, 'view' => $view, 'delete' => $delete, 'copy' => $copy])
            ->and($edit->getGroup())->toBe($group)
            ->and($view->getGroup())->toBe($more);
    });
});

describe('clone', function (): void {
    it('clones its members once, re-parented to the clone, so the member it maps is the member it renders', function (): void {
        $group = ActionGroup::make([$edit = Action::make('edit'), $more = ActionGroup::make([$view = Action::make('view')])]);

        $clone = $group->getClone();
        [$clonedEdit, $clonedMore] = $clone->getActions();
        $clone->getFlatActions()['edit']->record($lamp = new Product('Lamp'));

        expect($clonedEdit)->not->toBe($edit)
            ->and($clonedEdit)->toBe($clone->getFlatActions()['edit'])
            ->and($clonedEdit->getGroup())->toBe($clone)
            ->and($clonedMore)->not->toBe($more)
            ->and($clonedMore->getActions()[0])->not->toBe($view)
            ->and($clonedMore->getActions()[0])->toBe($clone->getFlatActions()['view'])
            ->and($clonedEdit->getRecord())->toBe($lamp)
            ->and($edit->getRecord())->toBeNull();
    });
});

describe('visibility', function (): void {
    it('leaves its parent without it when every member is hidden, it has none or it is hidden itself, and stays for one visible member', function (Closure $nested, bool $shown): void {
        $render = $this->renderViewComponent(ActionGroup::make([Action::make('edit'), $nested()]));

        expect($render)->toContainElement(SupportSelectors::DROPDOWN_ITEM, 'Edit')
            ->and($render->filter(SupportSelectors::DROPDOWN_ITEM)->each(static fn (Crawler $item): string => $item->text()))
            ->toBe($shown ? ['Edit', 'More', 'View'] : ['Edit']);
    })->with([
        'every member hidden' => [fn (): ActionGroup => ActionGroup::make([Action::make('view')->hidden()])->label('More'), false],
        'no member' => [fn (): ActionGroup => ActionGroup::make([])->label('More'), false],
        'hidden itself' => [fn (): ActionGroup => ActionGroup::make([Action::make('view')])->label('More')->hidden(), false],
        'not visible itself' => [fn (): ActionGroup => ActionGroup::make([Action::make('view')])->label('More')->visible(false), false],
        'one visible member' => [fn (): ActionGroup => ActionGroup::make([Action::make('drop')->hidden(), Action::make('view')])->label('More'), true],
    ]);

    it('asks again on every render whether it is hidden', function (): void {
        $hidden = false;
        $group = ActionGroup::make([Action::make('edit'), ActionGroup::make([Action::make('view')])->label('More')->hidden(static function () use (&$hidden): bool {
            return $hidden;
        })]);

        expect($this->renderViewComponent($group))->toContainElement(SupportSelectors::DROPDOWN_ITEM, 'More');

        $hidden = true;

        expect($this->renderViewComponent($group)->filter(SupportSelectors::DROPDOWN_ITEM)->each(static fn (Crawler $item): string => $item->text()))->toBe(['Edit']);
    });
});

describe('context', function (): void {
    it('hands its closures its host and binds every member to it, over a host a member was given', function (): void {
        $group = ActionGroup::make([
            $edit = Action::make('edit'),
            $view = Action::make('view')->liveComponent(new ToolbarHost()),
            ActionGroup::make([$copy = Action::make('copy')]),
        ])->liveComponent($host = new ToolbarHost());
        $liveComponent = static fn (object $liveComponent): object => $liveComponent;

        expect($group->evaluate($liveComponent))->toBe($host)
            ->and($edit->evaluate($liveComponent))->toBe($host)
            ->and($view->evaluate($liveComponent))->toBe($host)
            ->and($copy->evaluate($liveComponent))->toBe($host);
    });

    it("hands its closures its own record and class, else its parent group's, stopping at its own even when it is empty or not of its class", function (ActionGroup $group, ?object $record, ?string $recordClass): void {
        expect($group->evaluate(static fn (mixed $record, ?string $recordClass): array => [$record, $recordClass]))->toBe([$record, $recordClass]);
    })->with([
        'its own' => fn (): array => [ActionGroup::make([])->record($lamp = new Product('Lamp')), $lamp, Product::class],
        "its parent's" => function (): array {
            ActionGroup::make([$nested = ActionGroup::make([])])->record($lamp = new Product('Lamp'));

            return [$nested, $lamp, Product::class];
        },
        "its parent's record class" => function (): array {
            ActionGroup::make([$nested = ActionGroup::make([])])->recordClass(Product::class);

            return [$nested, null, Product::class];
        },
        'its own empty one' => function (): array {
            ActionGroup::make([$nested = ActionGroup::make([])->record($empty = new ArrayObject())])->record(new Product('Lamp'));

            return [$nested, $empty, ArrayObject::class];
        },
        'its own of another class' => function (): array {
            ActionGroup::make([$nested = ActionGroup::make([])->recordClass(Product::class)->record(new stdClass())])->record(new Product('Lamp'));

            return [$nested, null, Product::class];
        },
        'none, alone' => fn (): array => [ActionGroup::make([]), null, null],
    ]);

    it("hands its closures its schema, its component, the component's state, the schema's state and a field by name and by type", function (): void {
        $render = $this->renderSchemaComponent(Fieldset::make('Address')->statePath('address')->components([
            TextInput::make('street'),
            ActionGroup::make([Action::make('edit')])->button()->label(static fn (?Schema $schema, ?Component $component, mixed $state, mixed $schemaState, Get $get, Get $lookup): string => json_encode([
                'schema' => $schema?->getStatePath(),
                'component' => $component?->getLabel(),
                'state' => $state,
                'schemaState' => $schemaState,
                'get' => $get('address.street'),
                'lookup' => $lookup('address.street'),
            ], JSON_THROW_ON_ERROR)),
        ]), ['address' => ['street' => 'Main']]);

        expect($render)->toContainElement(SupportSelectors::BUTTON, json_encode([
            'schema' => 'data.address',
            'component' => 'Address',
            'state' => ['street' => 'Main'],
            'schemaState' => ['address' => ['street' => 'Main']],
            'get' => 'Main',
            'lookup' => 'Main',
        ], JSON_THROW_ON_ERROR));
    });

    it("hands a group among the actions of a component with no state path of its own that component's state", function (): void {
        $render = $this->renderSchemaComponent(Section::make('Address')->headerActions([
            ActionGroup::make([Action::make('edit')])->button()->label(static fn (mixed $state): string => json_encode($state, JSON_THROW_ON_ERROR)),
        ])->components([TextInput::make('street')]), ['street' => 'Main']);

        expect($render)->toContainElement(SupportSelectors::BUTTON, json_encode(['street' => 'Main'], JSON_THROW_ON_ERROR));
    });
});

describe('triggers', function (): void {
    it('names its trigger "Actions" unless it is labelled, as the accessible name of an icon button and the text of a button', function (): void {
        expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])))->toContainButtons(SupportSelectors::ICON_BUTTON, ['Actions'])
            ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->label('More')))->toContainButtons(SupportSelectors::ICON_BUTTON, ['More'])
            ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->button()))->toContainElement(SupportSelectors::BUTTON, 'Actions')
            ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->button()->label('More')))->toContainElement(SupportSelectors::BUTTON, 'More');
    });

    it('triggers its dropdown from the look it picks over the one it was given by default, an icon button with neither', function (Closure $group, string $look): void {
        expect($this->renderViewComponent($group()))->toContainButtons(SupportSelectors::DROPDOWN_TRIGGER . ' ' . $look, ['Actions']);
    })->with([
        'neither' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')]), SupportSelectors::ICON_BUTTON],
        'a button by default' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->defaultTriggerTemplate(ActionGroup::BUTTON_TEMPLATE), SupportSelectors::BUTTON],
        'an icon button over a button by default' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->defaultTriggerTemplate(ActionGroup::BUTTON_TEMPLATE)->iconButton(), SupportSelectors::ICON_BUTTON],
        'a button' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->button(), SupportSelectors::BUTTON],
        'a link' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->link(), 'button' . SupportSelectors::LINK],
        'a row' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->triggerTemplate(ActionGroup::GROUPED_TEMPLATE), SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER],
    ]);

    it('renders an uncoloured trigger gray, and a coloured one in its colour', function (Closure $group, string $look): void {
        expect($this->renderViewComponent($group()))->toContainButtons($look . SupportSelectors::GRAY, ['Actions'])
            ->and($this->renderViewComponent($group()->color('danger')))->toContainButtons($look . SupportSelectors::COLORED . SupportSelectors::color('danger'), ['Actions']);
    })->with([
        'an icon button' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')]), SupportSelectors::ICON_BUTTON],
        'a button' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->button(), SupportSelectors::BUTTON],
        'a link' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->link(), SupportSelectors::LINK],
    ]);

    it('shows no icon on its button trigger unless it is given one', function (): void {
        $plain = $this->renderViewComponent(ActionGroup::make([Action::make('edit')])->button());

        expect($plain)->toContainElement(SupportSelectors::BUTTON, 'Actions')
            ->and($plain->filter(SupportSelectors::BUTTON . ' ' . SupportSelectors::ICON))->toHaveCount(0)
            ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->button()->icon('support:star')))->toContainElement(SupportSelectors::BUTTON, 'StarActions');
    });

    it('outlines its button trigger', function (): void {
        expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->button()->outlined()))->toContainElement(SupportSelectors::BUTTON . SupportSelectors::OUTLINED, 'Actions');
    });

    it('sizes its trigger, medium unless it is sized', function (Closure $group, string $look): void {
        expect($this->renderViewComponent($group()))->toContainButtons($look . SupportSelectors::MEDIUM, ['Actions'])
            ->and($this->renderViewComponent($group()->size(Size::Small)))->toContainButtons($look . SupportSelectors::size('sm'), ['Actions']);
    })->with([
        'an icon button' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')]), SupportSelectors::ICON_BUTTON],
        'a button' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->button(), SupportSelectors::BUTTON],
        'a link' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->link(), SupportSelectors::LINK],
    ]);

    it("sizes the icon of its link trigger, and of its row in a parent's dropdown, a row's small unless it is sized", function (): void {
        $row = static fn (ActionGroup $nested): ActionGroup => ActionGroup::make([$nested->label('More')->icon('support:star')]);

        expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->link()->icon('support:star')->iconSize(IconSize::Large)))
            ->toContainElement(SupportSelectors::LINK . ' ' . SupportSelectors::ICON . SupportSelectors::size('lg'), 'Star')
            ->and($this->renderViewComponent($row(ActionGroup::make([Action::make('edit')]))))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . ' ' . SupportSelectors::ICON . SupportSelectors::size('sm'), 'Star')
            ->and($this->renderViewComponent($row(ActionGroup::make([Action::make('edit')])->iconSize(IconSize::Large))))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . ' ' . SupportSelectors::ICON . SupportSelectors::size('lg'), 'Star');
    });

    it("titles its link trigger, and its row in a parent's dropdown, with its tooltip", function (): void {
        expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->link()->tooltip('More options')))
            ->toContainButtons(SupportSelectors::LINK . '[title="More options"]', ['Actions'])
            ->and($this->renderViewComponent(ActionGroup::make([ActionGroup::make([Action::make('edit')])->label('More')->tooltip('More options')])))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . '[title="More options"]', 'More');
    });

    it("renders its extra attributes on its trigger's root, its classes beside the root's own and its null and false ones dropped", function (Closure $group, string $look): void {
        $attributes = ['class' => ['x-wide', 'x-dark'], 'data-tour' => 'more', 'data-none' => null, 'data-off' => false];

        expect($this->renderViewComponent($group($attributes)))
            ->toContainElement($look . '.x-wide.x-dark[data-tour="more"]:not([data-none]):not([data-off])');
    })->with([
        'an icon button' => [fn (array $attributes): ActionGroup => ActionGroup::make([Action::make('edit')])->extraAttributes($attributes), SupportSelectors::ICON_BUTTON],
        'a button' => [fn (array $attributes): ActionGroup => ActionGroup::make([Action::make('edit')])->button()->extraAttributes($attributes), SupportSelectors::BUTTON],
        'a link' => [fn (array $attributes): ActionGroup => ActionGroup::make([Action::make('edit')])->link()->extraAttributes($attributes), SupportSelectors::LINK],
        "a row of a parent's dropdown" => [fn (array $attributes): ActionGroup => ActionGroup::make([ActionGroup::make([Action::make('edit')])->extraAttributes($attributes)]), SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER],
    ]);

    it("renders its extra dropdown attributes on its dropdown's root, the last set unless merged, its classes beside the root's own and its null and false ones dropped", function (): void {
        $dropdown = $this->renderViewComponent(ActionGroup::make([Action::make('edit')])
            ->extraDropdownAttributes(['data-first' => 'replaced'])
            ->extraDropdownAttributes(['class' => ['x-wide', 'x-dark'], 'data-none' => null, 'data-off' => false])
            ->extraDropdownAttributes(static fn (): array => ['data-tour' => 'more'], merge: true))
            ->filter(SupportSelectors::DROPDOWN);

        expect($dropdown->matches(SupportSelectors::DROPDOWN . '.x-wide.x-dark[data-tour="more"]:not([data-first]):not([data-none]):not([data-off])'))->toBeTrue()
            ->and($dropdown->filter(SupportSelectors::DROPDOWN_TRIGGER . ' [data-tour]'))->toHaveCount(0);
    });

    it("renders its link trigger as a button, its label for screen readers only when it hides it", function (): void {
        $hidden = $this->renderViewComponent(ActionGroup::make([Action::make('edit')])->link()->label('More')->hiddenLabel());

        expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->link()->label('More')))->toContainElement('button' . SupportSelectors::LINK, 'More')
            ->and($hidden)->toContainButtons('button' . SupportSelectors::LINK, ['More'])
            ->and($hidden->filter(SupportSelectors::LINK)->text())->toBe('');
    });

    it("shows its own icon on its row in a parent's dropdown, else its grouped icon, else no icon", function (Closure $nested, string $text, int $icons): void {
        $row = $this->renderViewComponent(ActionGroup::make([$nested()->label('More')]))->filter(SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER);

        expect($row->text())->toBe($text)
            ->and($row->filter(SupportSelectors::ICON))->toHaveCount($icons);
    })->with([
        'a grouped icon' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->groupedIcon('support:heart'), 'HeartMore', 1],
        'its own icon over a grouped one' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->icon('support:star')->groupedIcon('support:heart'), 'StarMore', 1],
        'neither' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')]), 'More', 0],
    ]);

    it("colours its row in a parent's dropdown, gray unless it is coloured", function (): void {
        expect($this->renderViewComponent(ActionGroup::make([ActionGroup::make([Action::make('edit')])->label('More')->color('danger')])))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . SupportSelectors::color('danger'), 'More')
            ->and($this->renderViewComponent(ActionGroup::make([ActionGroup::make([Action::make('edit')])->label('More')])))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . SupportSelectors::GRAY, 'More');
    });
});

describe('members', function (): void {
    it("turns a nested group into a row of a dropdown parent and into a button of an inline one, unless it picks its own trigger", function (): void {
        $nested = static fn (): ActionGroup => ActionGroup::make([Action::make('view')])->label('More');

        expect($this->renderViewComponent(ActionGroup::make([$nested()])))->toContainElement(SupportSelectors::DROPDOWN_PANEL . ' ' . SupportSelectors::DROPDOWN_TRIGGER . ' ' . SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER, 'More')
            ->and($this->renderViewComponent(ActionGroup::make([$nested()])->dropdown(false)))->toContainElement(Selectors::INLINE_GROUP . ' ' . SupportSelectors::DROPDOWN_TRIGGER . ' ' . SupportSelectors::BUTTON, 'More')
            ->and($this->renderViewComponent(ActionGroup::make([$nested()->iconButton()])))->toContainButtons(SupportSelectors::DROPDOWN_PANEL . ' ' . SupportSelectors::DROPDOWN_TRIGGER . ' ' . SupportSelectors::ICON_BUTTON, ['More']);
    });

    it("opens a nested group's dropdown to the right unless it is placed, its own below", function (): void {
        $render = $this->renderViewComponent(ActionGroup::make([ActionGroup::make([Action::make('view')])->label('More'), ActionGroup::make([Action::make('copy')])->label('Copy')->dropdownPlacement('left-start')]));

        expect($render->filter(SupportSelectors::DROPDOWN)->each(static fn (Crawler $dropdown): ?string => $dropdown->attr('data-sylament--support--dropdown-placement-value')))
            ->toBe(['bottom-start', 'right-start', 'left-start']);
    });

    it('lays out the visible members of an inline group as buttons in a row, unless one picks its own look', function (): void {
        $render = $this->renderViewComponent(ActionGroup::make([Action::make('edit'), Action::make('drop')->hidden(), Action::make('view')->iconButton(), Action::make('copy')])->dropdown(false));

        expect($render)->toContainButtons(Selectors::INLINE_GROUP . ' ' . SupportSelectors::BUTTON, ['Edit', 'Copy'])
            ->toContainButtons(Selectors::INLINE_GROUP . ' ' . SupportSelectors::ICON_BUTTON, ['View']);
    });

    it("splits its dropdown into a section per run of visible members, a nested group's own dropdown one member of a run, and a nested group without one a section of its visible members as rows", function (): void {
        $render = $this->renderViewComponent(ActionGroup::make([
            Action::make('edit'),
            ActionGroup::make([Action::make('view')])->label('More'),
            Action::make('drop')->hidden(),
            ActionGroup::make([Action::make('copy'), Action::make('purge')->hidden(), Action::make('share')->link()])->dropdown(false),
            Action::make('archive'),
        ]));

        expect($render->filter('.sy-dropdown-list')->each(static fn (Crawler $section): string => $section->text()))->toBe(['Edit More View', 'View', 'Copy Share', 'Archive'])
            ->and($render)->toContainElement(SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER, 'Copy')
            ->toContainElement(SupportSelectors::LINK . Selectors::TRIGGER, 'Share');
    });
});

describe('serialization', function (): void {
    it('restores a group that renders as the one it serialized, its members and nested groups included', function (): void {
        $group = ActionGroup::make([Action::make('edit'), ActionGroup::make([Action::make('view')])->label('Open')->icon('support:heart')])
            ->button()
            ->label('More')
            ->icon('support:star')
            ->color('danger')
            ->size(Size::Small)
            ->outlined()
            ->tooltip('More options')
            ->extraAttributes(['data-tour' => 'more'])
            ->dropdownPlacement('top-end')
            ->dropdownOffset(4)
            ->dropdownFlip(false)
            ->dropdownTeleport()
            ->dropdownWidth('20rem')
            ->dropdownMaxHeight('10rem');

        $restored = ActionGroup::fromArray($group->toArray());

        expect($restored->toArray())->toBe($group->toArray())
            ->and($this->renderViewComponent($restored))->toContainElement(SupportSelectors::BUTTON . SupportSelectors::OUTLINED . SupportSelectors::color('danger') . SupportSelectors::size('sm') . '[data-tour="more"]', 'StarMore')
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER, 'HeartOpen');
    });

    it('writes its sizes and a plain enum icon as scalars, and keeps a scalable icon to resolve at the size it renders', function (): void {
        $plain = ActionGroup::make([Action::make('edit')])->icon(Icon::Star)->iconSize(IconSize::Large)->size(Size::Small)->toArray();
        $scalable = ActionGroup::make([Action::make('edit')])->icon(SizedIcon::Star)->size(Size::Large)->toArray();

        expect($plain)->toMatchArray(['icon' => 'support:star', 'iconSize' => 'lg', 'size' => 'sm'])
            ->and($scalable)->toMatchArray(['icon' => SizedIcon::Star, 'size' => 'lg'])
            ->and($this->renderViewComponent(ActionGroup::fromArray($scalable)))->toContainButtons(SupportSelectors::ICON_BUTTON . SupportSelectors::size('lg'), ['Actions'])
            ->toContainElement(SupportSelectors::ICON_BUTTON . ' ' . SupportSelectors::ICON, 'Star, large');
    });

    it('restores a payload without the flip key with flipping off, and one without the dropdown key with its dropdown', function (): void {
        expect($this->renderViewComponent(ActionGroup::fromArray(['actions' => [['name' => 'edit']]])))
            ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('flip', 'false'))
            ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])))
            ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('flip', 'true'));
    });

    it("restores only its own trigger templates, any other falling back to the trigger its place gives it", function (): void {
        expect($this->renderViewComponent(ActionGroup::fromArray(['actions' => [['name' => 'edit']], 'triggerTemplate' => '@Evil/anything.html.twig'])))
            ->toContainButtons(SupportSelectors::ICON_BUTTON, ['Actions'])
            ->and($this->renderViewComponent(ActionGroup::fromArray(['actions' => [['name' => 'edit']], 'triggerTemplate' => ActionGroup::LINK_TEMPLATE])))
            ->toContainButtons(SupportSelectors::LINK, ['Actions'])
            ->and($this->renderViewComponent(ActionGroup::fromArray(['actions' => [['actions' => [['name' => 'view']], 'label' => 'More', 'triggerTemplate' => '@Evil/anything.html.twig']]])))
            ->toContainElement(SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER, 'More');
    });

    it('restores the nested groups of a subclass as that subclass', function (): void {
        expect($this->renderViewComponent(BulkActionGroup::fromArray(['actions' => [['actions' => [['name' => 'view']]]]]))->filter(SupportSelectors::DROPDOWN . Selectors::BULK_ONLY))
            ->toHaveCount(2);
    });
});

it('translates its label given as a message, and a string label as a key in the default domain under translateLabel()', function (): void {
    $translator = new Translator('en');
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource('array', ['more' => 'More (from the default domain)'], 'en');

    $translated = ActionGroup::make([Action::make('edit')])->label('more')->translateLabel();
    $translated->setTranslator($translator);

    $literal = ActionGroup::make([Action::make('edit')])->label('more');
    $literal->setTranslator($translator);

    expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->label(t('trigger.label', [], 'fixtures-actions'))))->toContainButtons(SupportSelectors::ICON_BUTTON, ['Archive (translated)'])
        ->and($this->renderViewComponent($translated))->toContainButtons(SupportSelectors::ICON_BUTTON, ['More (from the default domain)'])
        ->and($this->renderViewComponent($literal))->toContainButtons(SupportSelectors::ICON_BUTTON, ['more']);
});
