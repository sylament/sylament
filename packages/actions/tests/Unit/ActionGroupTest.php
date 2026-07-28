<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionHost;
use stdClass;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Actions\Tests\Fixtures\StateFieldFixture;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Support\Twig\ComponentAttributeBag;

it('keeps its actions and back-references each one', function (): void {
    $edit = Action::make('edit');
    $delete = Action::make('delete');
    $group = ActionGroup::make([$edit, $delete]);

    expect($group->getActions())->toBe([$edit, $delete])
        ->and($edit->getGroup())->toBe($group)
        ->and($delete->getGroup())->toBe($group);
});

it('builds an eager name-keyed flat map', function (): void {
    $edit = Action::make('edit');
    $delete = Action::make('delete');

    expect(ActionGroup::make([$edit, $delete])->getFlatActions())
        ->toBe(['edit' => $edit, 'delete' => $delete]);
});

it('recurses nested groups into the flat map', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $group = ActionGroup::make([$edit, ActionGroup::make([$archive, $delete])]);

    expect($group->getFlatActions())
        ->toBe(['edit' => $edit, 'archive' => $archive, 'delete' => $delete]);
});

it('rejects an unnamed action', function (): void {
    ActionGroup::make([new class (null) extends Action {}]);
})->throws(LogicException::class);

it('defaults its label to Actions and accepts an override', function (): void {
    expect(ActionGroup::make([])->getLabel())->toBe('Actions')
        ->and(ActionGroup::make([])->label('More')->getLabel())->toBe('More')
        ->and(ActionGroup::make([])->label(fn (): string => 'Extra')->getLabel())->toBe('Extra');
});

it('falls back to an ellipsis icon that an explicit icon overrides', function (): void {
    expect(ActionGroup::make([])->getIcon(ActionGroup::DEFAULT_ICON))->toBe('heroicons:ellipsis-vertical')
        ->and(ActionGroup::make([])->icon('heroicons:x-mark')->getIcon(ActionGroup::DEFAULT_ICON))->toBe('heroicons:x-mark');
});

it('is hidden when every child is hidden', function (): void {
    $group = ActionGroup::make([Action::make('edit')->hidden(), Action::make('delete')->hidden()]);

    expect($group->isHidden())->toBeTrue()
        ->and($group->isVisible())->toBeFalse();
});

it('is visible while any child is visible', function (): void {
    expect(ActionGroup::make([Action::make('edit')->hidden(), Action::make('delete')])->isHidden())
        ->toBeFalse();
});

it('hides on its own flag even with visible children', function (): void {
    expect(ActionGroup::make([Action::make('edit')])->hidden()->isHidden())->toBeTrue();
});

it('is hidden when empty', function (): void {
    expect(ActionGroup::make([])->isHidden())->toBeTrue();
});

it('forwards the live component to nested flat actions', function (): void {
    $liveComponent = new ActionHost();
    $delete = Action::make('delete');
    $group = ActionGroup::make([Action::make('edit'), ActionGroup::make([$delete])]);

    $group->liveComponent($liveComponent);

    expect($delete->getLiveComponent())->toBe($liveComponent);
});

it('forwards the record and lets actions fall back to it', function (): void {
    $record = new stdClass();
    $edit = Action::make('edit');

    ActionGroup::make([$edit])->record($record);

    expect($edit->getRecord())->toBe($record);
});

it('lets an action record win over the group record', function (): void {
    $own = new stdClass();
    $edit = Action::make('edit')->record($own);

    ActionGroup::make([$edit])->record(new stdClass());

    expect($edit->getRecord())->toBe($own);
});

it('injects its own record into evaluated closures via $record', function (): void {
    $record = new stdClass();
    $group = ActionGroup::make([Action::make('edit')])
        ->record($record)
        ->hidden(fn ($record): bool => $record !== null);

    expect($group->isHidden())->toBeTrue();
});

it('injects its live component into evaluated closures via $liveComponent', function (): void {
    $liveComponent = new ActionHost();
    $group = ActionGroup::make([Action::make('edit')])
        ->liveComponent($liveComponent)
        ->hidden(fn ($liveComponent): bool => $liveComponent !== null);

    expect($group->isHidden())->toBeTrue();
});

it('injects the schema container and component into evaluated closures', function (): void {
    $schema = Schema::make();
    $section = Section::make('Box')->container($schema);

    $group = ActionGroup::make([])
        ->schemaContainer($schema)
        ->schemaComponent($section);

    expect($group->evaluate(fn ($schema): Schema => $schema))->toBe($schema)
        ->and($group->evaluate(fn ($component): Section => $component))->toBe($section)
        ->and($group->evaluate(fn ($state): mixed => $state))->toBeNull();
});

it('injects $get scoped to the schema component', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada', 'subtitle' => 'Bo'];

    $title = StateFieldFixture::make('title');
    $subtitle = StateFieldFixture::make('subtitle');
    Schema::make($host)->statePath('data')->components([$title, $subtitle])->getComponents();

    $group = ActionGroup::make([])->schemaComponent($subtitle);

    expect($group->evaluate(fn ($get): mixed => $get('title')))->toBe('Ada');
});

it('pushes the grouped item template onto action children as a default', function (): void {
    $edit = Action::make('edit');
    ActionGroup::make([$edit])->getActions();

    expect($edit->getTemplate())->toBe(Action::GROUPED_TEMPLATE);
});

it('keeps the normal action template under dropdown(false)', function (): void {
    $edit = Action::make('edit');
    ActionGroup::make([$edit])->dropdown(false)->getActions();

    expect($edit->getTemplate())->toBe('@SylamentActions/components/button-action.html.twig');
});

it('never clobbers an explicit action template', function (): void {
    $edit = Action::make('edit')->template('custom.html.twig');
    ActionGroup::make([$edit])->getActions();

    expect($edit->getTemplate())->toBe('custom.html.twig');
});

it('defaults to the icon-button trigger template', function (): void {
    $group = ActionGroup::make([]);

    expect($group->getTriggerTemplate())->toBe(ActionGroup::ICON_BUTTON_TEMPLATE)
        ->and($group->isIconButton())->toBeTrue()
        ->and($group->isButton())->toBeFalse();
});

it('switches to the button trigger template', function (): void {
    $group = ActionGroup::make([])->button();

    expect($group->getTriggerTemplate())->toBe(ActionGroup::BUTTON_TEMPLATE)
        ->and($group->isButton())->toBeTrue()
        ->and($group->isIconButton())->toBeFalse();
});

it('lets an explicit trigger template win over the default', function (): void {
    $group = ActionGroup::make([])->triggerTemplate('custom-trigger.html.twig');

    expect($group->getTriggerTemplate())->toBe('custom-trigger.html.twig')
        ->and($group->isIconButton())->toBeFalse();
});

it('has no extra dropdown attributes by default', function (): void {
    $group = ActionGroup::make([]);

    expect($group->hasExtraDropdownAttributes())->toBeFalse()
        ->and($group->getExtraDropdownAttributes())->toBe([]);
});

it('replaces previously set extra dropdown attributes by default', function (): void {
    $group = ActionGroup::make([])
        ->extraDropdownAttributes(['data-a' => '1'])
        ->extraDropdownAttributes(['data-b' => '2']);

    expect($group->getExtraDropdownAttributes())->toBe(['data-b' => '2']);
});

it('appends extra dropdown attribute entries with merge', function (): void {
    $group = ActionGroup::make([])
        ->extraDropdownAttributes(['data-a' => '1'])
        ->extraDropdownAttributes(['data-b' => '2'], merge: true);

    expect($group->getExtraDropdownAttributes())->toEqualCanonicalizing(['data-a' => '1', 'data-b' => '2']);
});

it('evaluates extra dropdown attribute closures', function (): void {
    $group = ActionGroup::make([])
        ->extraDropdownAttributes(fn (): array => ['data-computed' => 'yes']);

    expect($group->getExtraDropdownAttributes())->toBe(['data-computed' => 'yes']);
});

it('exposes the merged dropdown attributes as a bag', function (): void {
    $bag = ActionGroup::make([])
        ->extraDropdownAttributes(['class' => 'custom'])
        ->getExtraDropdownAttributeBag();

    expect($bag)->toBeInstanceOf(ComponentAttributeBag::class)
        ->and($bag->getAttributes())->toBe(['class' => ['custom']]);
});

it('is not outlined by default and toggles through outlined()', function (): void {
    expect(ActionGroup::make([])->isOutlined())->toBeFalse()
        ->and(ActionGroup::make([])->outlined()->isOutlined())->toBeTrue()
        ->and(ActionGroup::make([])->outlined(fn (): bool => false)->isOutlined())->toBeFalse()
        ->and(ActionGroup::make([])->outlined()->hasOutlined())->toBeTrue();
});

it('round-trips a grouped icon', function (): void {
    expect(ActionGroup::make([])->getGroupedIcon())->toBeNull()
        ->and(ActionGroup::make([])->hasGroupedIcon())->toBeFalse()
        ->and(ActionGroup::make([])->groupedIcon('heroicons:pencil')->getGroupedIcon())->toBe('heroicons:pencil')
        ->and(ActionGroup::make([])->groupedIcon(fn (): string => 'heroicons:trash')->getGroupedIcon())->toBe('heroicons:trash')
        ->and(ActionGroup::make([])->groupedIcon(fn (): string => 'heroicons:trash')->hasGroupedIcon())->toBeTrue();
});

it('keeps the trigger and dropdown attribute bags independent', function (): void {
    $group = ActionGroup::make([])
        ->extraAttributes(['data-trigger' => '1'])
        ->extraDropdownAttributes(['data-dropdown' => '2']);

    expect($group->getExtraAttributes())->toBe(['data-trigger' => '1'])
        ->and($group->getExtraDropdownAttributes())->toBe(['data-dropdown' => '2']);
});

it('splits dropdown children into divider sections around flattened subgroups', function (): void {
    $group = ActionGroup::make([
        $a = Action::make('a'),
        $b = Action::make('b'),
        ActionGroup::make([$c = Action::make('c'), $d = Action::make('d'), Action::make('hiddenChild')->hidden()])->dropdown(false),
        $e = Action::make('e'),
    ]);

    expect($group->getDropdownActionLists())->toBe([[$a, $b], [$c, $d], [$e]]);
});

it('keeps a dropdown-enabled subgroup inline as a submenu entry', function (): void {
    $group = ActionGroup::make([
        $a = Action::make('a'),
        $sub = ActionGroup::make([Action::make('b')]),
    ]);

    expect($group->getDropdownActionLists())->toBe([[$a, $sub]]);
});

it('skips hidden children when building the section lists', function (): void {
    $group = ActionGroup::make([
        $a = Action::make('a'),
        Action::make('b')->hidden(),
        ActionGroup::make([Action::make('c')->hidden()])->dropdown(false),
    ]);

    expect($group->getDropdownActionLists())->toBe([[$a]]);
});

it('stamps the submenu placement default on nested groups without overriding explicit placements', function (): void {
    $sub = ActionGroup::make([Action::make('a')]);
    $explicit = ActionGroup::make([Action::make('b')])->dropdownPlacement('left-start');
    ActionGroup::make([$sub, $explicit]);

    expect($sub->getDropdownPlacement())->toBe('right-start')
        ->and($explicit->getDropdownPlacement())->toBe('left-start');
});

it('propagates the grouped trigger template default to nested groups', function (): void {
    $sub = ActionGroup::make([Action::make('a')]);
    $group = ActionGroup::make([$sub]);
    $group->getActions();

    expect($sub->getTriggerTemplate())->toBe(ActionGroup::GROUPED_TEMPLATE)
        ->and($sub->iconButton()->getTriggerTemplate())->toBe(ActionGroup::ICON_BUTTON_TEMPLATE);
});
