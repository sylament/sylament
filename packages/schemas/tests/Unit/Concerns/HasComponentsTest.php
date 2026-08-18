<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Html;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasComponentsFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Twig\Markup;

it('evaluates the closure lazily when reading the components', function (): void {
    $first = new Component();
    $components = [$first];
    $fixture = new HasComponentsFixture()->components(function () use (&$components): array {
        return $components;
    });

    $second = new Component();
    $components = [$first, $second];

    expect($fixture->getComponents())->toBe([$first, $second]);
});

it('assigns itself as the container of each component', function (): void {
    $component = new Component();
    $fixture = new HasComponentsFixture()->components([$component]);

    $fixture->getComponents();

    expect($component->getContainer())->toBe($fixture);
});

it('caches the resolved components', function (): void {
    $fixture = new HasComponentsFixture()->components([new Component()]);

    expect($fixture->getComponents())->toBe($fixture->getComponents());
});

it('rebuilds the cache when components() is called again', function (): void {
    $fixture = new HasComponentsFixture()->components([new Component()]);
    $first = $fixture->getComponents();

    $replacement = [new Component()];
    $fixture->components($replacement);

    expect($fixture->getComponents())->toBe($replacement)
        ->and($fixture->getComponents())->not->toBe($first);
});

it('treats a components closure returning null as empty', function (): void {
    $fixture = new HasComponentsFixture()->components(fn (): ?array => null);

    expect($fixture->getComponents())->toBe([]);
});

it('coerces a string component into a Text component', function (): void {
    $fixture = new HasComponentsFixture()->components(['Hello']);

    $components = $fixture->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Text::class)
        ->and($components[0]->getContent())->toBe('Hello');
});

it('coerces a non-string, non-component value into an Html component', function (): void {
    $markup = new Markup('<b>raw</b>', 'UTF-8');
    $fixture = new HasComponentsFixture()->components([$markup]);

    $components = $fixture->getComponents();

    expect($components[0])->toBeInstanceOf(Html::class)
        ->and($components[0]->getContent())->toBe($markup);
});

it('leaves an existing component untouched while coercing its siblings', function (): void {
    $component = new Component();
    $fixture = new HasComponentsFixture()->components([$component, 'text', new Markup('m', 'UTF-8')]);

    $components = $fixture->getComponents();

    expect($components[0])->toBe($component)
        ->and($components[1])->toBeInstanceOf(Text::class)
        ->and($components[2])->toBeInstanceOf(Html::class);
});

it('wraps and coerces a single non-array component value', function (): void {
    $fixture = new HasComponentsFixture()->components('Just text');

    $components = $fixture->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Text::class);
});

it('assigns itself as the container of a coerced component', function (): void {
    $fixture = new HasComponentsFixture()->components(['text']);

    expect($fixture->getComponents()[0]->getContainer())->toBe($fixture);
});

it('keeps an Action child as-is and stamps this schema as its container', function (): void {
    $action = Action::make('save');
    $fixture = new HasComponentsFixture()->components([$action]);

    expect($fixture->getComponents())->toBe([$action])
        ->and($action->getSchemaContainer())->toBe($fixture);
});

it('keeps an ActionGroup child as-is and stamps this schema as its container', function (): void {
    $group = ActionGroup::make([Action::make('save')]);
    $fixture = new HasComponentsFixture()->components([$group]);

    expect($fixture->getComponents())->toBe([$group])
        ->and($group->getSchemaContainer())->toBe($fixture);
});

it('applies the modifyActionsUsing hook to an Action child when resolving components', function (): void {
    $schema = Schema::make()
        ->modifyActionsUsing(fn (Action $action): Action => $action->label('Hooked'))
        ->components([Action::make('save')]);

    expect($schema->getComponents()[0]->getLabel())->toBe('Hooked');
});

it('applies the modifyActionGroupsUsing hook to an ActionGroup child when resolving components', function (): void {
    $schema = Schema::make()
        ->modifyActionGroupsUsing(fn (ActionGroup $group): ActionGroup => $group->label('Hooked'))
        ->components([ActionGroup::make([Action::make('save')])]);

    expect($schema->getComponents()[0]->getLabel())->toBe('Hooked');
});

it('excludes actions and action groups when withActions is false', function (): void {
    $component = new Component();
    $action = Action::make('save');
    $group = ActionGroup::make([Action::make('delete')]);
    $fixture = new HasComponentsFixture()->components([$component, $action, $group]);

    expect($fixture->getComponents(withActions: false))->toBe([$component])
        ->and($fixture->getComponents())->toBe([$component, $action, $group]);
});

it('excludes a hidden action when withHidden is false', function (): void {
    $visible = Action::make('save');
    $hidden = Action::make('delete')->hidden();
    $fixture = new HasComponentsFixture()->components([$visible, $hidden]);

    expect($fixture->getComponents(withHidden: false))->toBe([$visible])
        ->and($fixture->getComponents())->toBe([$visible, $hidden]);
});

it('coerces strings and Markup while keeping an action sibling untouched', function (): void {
    $action = Action::make('save');
    $fixture = new HasComponentsFixture()->components([$action, 'text', new Markup('m', 'UTF-8')]);

    $components = $fixture->getComponents();

    expect($components[0])->toBe($action)
        ->and($components[1])->toBeInstanceOf(Text::class)
        ->and($components[2])->toBeInstanceOf(Html::class);
});

it('returns all components including hidden ones by default', function (): void {
    $visible = new Component();
    $hidden = new Component()->hidden();
    $fixture = new HasComponentsFixture()->components([$visible, $hidden]);

    expect($fixture->getComponents())->toBe([$visible, $hidden]);
});

it('excludes hidden components when withHidden is false', function (): void {
    $visible = new Component();
    $hidden = new Component()->hidden();
    $fixture = new HasComponentsFixture()->components([$visible, $hidden]);

    expect($fixture->getComponents(withHidden: false))->toBe([$visible]);
});

it('excludes a component hidden by a closure when withHidden is false', function (): void {
    $shown = new Component();
    $gone = new Component()->hidden(fn (): bool => true);
    $fixture = new HasComponentsFixture()->components([$shown, $gone]);

    expect($fixture->getComponents(withHidden: false))->toBe([$shown]);
});

it('includes direct and nested actions in the flat components', function (): void {
    $action = Action::make('save');
    $nestedAction = Action::make('nested');
    $group = StateGroup::make()->components([$nestedAction]);
    $schema = Schema::make(new StateHostFixture())->components([$action, $group]);

    expect($schema->getFlatComponents())->toBe([$action, $group, $nestedAction]);
});

it('excludes direct and nested actions from the flat components when withActions is false', function (): void {
    $action = Action::make('save');
    $group = StateGroup::make()->components([Action::make('nested')]);
    $schema = Schema::make(new StateHostFixture())->components([$action, $group]);

    expect($schema->getFlatComponents(withActions: false))->toBe([$group]);
});

it('includes hidden components in the flat components by default', function (): void {
    $visible = new Component();
    $hidden = new Component()->hidden();
    $schema = Schema::make(new StateHostFixture())->components([$visible, $hidden]);

    expect($schema->getFlatComponents())->toBe([$visible, $hidden]);
});

it('excludes hidden components from the flat components when withHidden is false', function (): void {
    $visible = new Component();
    $hidden = new Component()->hidden();
    $schema = Schema::make(new StateHostFixture())->components([$visible, $hidden]);

    expect($schema->getFlatComponents(withHidden: false))->toBe([$visible]);
});

it('finds a nested component by its absolute key', function (): void {
    $section = Section::make()->key('author');
    $schema = Schema::make(new StateHostFixture())->key('schema')->components([$section]);

    expect($schema->getComponent('schema.author', isAbsoluteKey: true))->toBe($section);
});

it('resolves a relative key by prefixing it with the container key', function (): void {
    $section = Section::make()->key('author');
    $schema = Schema::make(new StateHostFixture())->key('schema')->components([$section]);

    expect($schema->getComponent('author'))->toBe($section);
});

it('does not find a hidden component by key unless withHidden is true', function (): void {
    $hidden = Section::make()->key('secret')->hidden();
    $schema = Schema::make(new StateHostFixture())->components([$hidden]);

    expect($schema->getComponent('secret'))->toBeNull()
        ->and($schema->getComponent('secret', withHidden: true))->toBe($hidden);
});

it('finds an Action child through a closure predicate only when withActions is true', function (): void {
    $action = Action::make('save');
    $schema = Schema::make()->components([$action]);

    $predicate = fn (Component|Action|ActionGroup $component): bool => $component instanceof Action;

    expect($schema->getComponent($predicate))->toBe($action)
        ->and($schema->getComponent($predicate, withActions: false))->toBeNull();
});

it('finds a component through a closure predicate', function (): void {
    $target = new Component();
    $other = new Component();
    $schema = Schema::make(new StateHostFixture())->components([$other, $target]);

    expect($schema->getComponent(fn (Component|Action|ActionGroup $component): bool => $component === $target))->toBe($target);
});

it('returns null for an unknown key', function (): void {
    $schema = Schema::make()->components([new Component()]);

    expect($schema->getComponent('missing'))->toBeNull();
});

it('resolves a top-level component by its absolute state path', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateField::make('title'),
        StateField::make('subtitle'),
    ]);

    $title = $schema->getComponents()[0];

    expect($schema->getComponentByStatePath('data.title'))->toBe($title);
});

it('resolves a nested component by descending into child schemas', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
    ]);

    $found = $schema->getComponentByStatePath('data.address.city');

    expect($found?->getStatePath())->toBe('data.address.city')
        ->and($found?->getStatePath(isAbsolute: false))->toBe('city');
});

it('returns null for a path owned by no component', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateField::make('title'),
    ]);

    expect($schema->getComponentByStatePath('data.nope'))->toBeNull();
});

it('does not descend into a skipped component while still matching it exactly', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
    ]);

    $group = $schema->getComponents()[0];

    expect($schema->getComponentByStatePath('data.address.city', [$group]))->toBeNull()
        ->and($schema->getComponentByStatePath('data.address', [$group]))->toBe($group);
});

it('resolves a direct Action child by name', function (): void {
    $action = Action::make('save');
    $schema = Schema::make()->components([new Component(), $action]);

    expect($schema->getAction('save'))->toBe($action);
});

it('resolves an action inside a direct ActionGroup child through its flat actions', function (): void {
    $inner = Action::make('delete');
    $schema = Schema::make()->components([ActionGroup::make([$inner])]);

    expect($schema->getAction('delete'))->toBe($inner);
});

it('resolves a nested action through an unkeyed child schema', function (): void {
    $action = Action::make('publish');
    $schema = Schema::make(new StateHostFixture())->components([
        Section::make()->components([$action]),
    ]);

    expect($schema->getAction('publish'))->toBe($action);
});

it('resolves a component-registered action only with the exact nested container key', function (): void {
    $action = Action::make('feature');
    $schema = Schema::make()->components([
        Section::make('Settings')->registerActions([$action]),
    ]);

    expect($schema->getAction('feature', 'settings::section'))->toBe($action)
        ->and($schema->getAction('feature'))->toBeNull();
});

it('strips a keyed component inheritance prefix when descending', function (): void {
    $action = Action::make('feature');
    $inner = StateGroup::make()->key('inner')->registerActions([$action]);
    $schema = Schema::make(new StateHostFixture())->components([
        StateGroup::make()->key('wrapper')->components([$inner]),
    ]);

    expect($schema->getAction('feature', 'wrapper.inner'))->toBe($action);
});

it('enters a keyed child schema only when the nested container key targets it', function (): void {
    $action = Action::make('approve');
    $slot = Schema::make()->key('slot')->components([$action]);
    $schema = Schema::make(new StateHostFixture())->components([
        StateGroup::make()->components($slot),
    ]);

    expect($schema->getAction('approve', 'slot'))->toBe($action)
        ->and($schema->getAction('approve'))->toBeNull();
});

it('does not resolve actions inside a hidden component', function (): void {
    $child = Action::make('inline');
    $registered = Action::make('feature');
    $schema = Schema::make(new StateHostFixture())->components([
        Section::make('Hidden zone')->components([$child])->registerActions([$registered])->hidden(),
    ]);

    expect($schema->getAction('inline'))->toBeNull()
        ->and($schema->getAction('feature', 'hidden-zone::section'))->toBeNull();
});

it('returns the first match in traversal order for a duplicate action name', function (): void {
    $nested = Action::make('save');
    $direct = Action::make('save');
    $schema = Schema::make(new StateHostFixture())->components([
        Section::make()->components([$nested]),
        $direct,
    ]);

    expect($schema->getAction('save'))->toBe($nested);
});

it('returns null for an unknown action name', function (): void {
    $schema = Schema::make()->components([Action::make('save')]);

    expect($schema->getAction('missing'))->toBeNull();
});
