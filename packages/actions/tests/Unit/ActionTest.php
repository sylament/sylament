<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Actions\Tests\Fixtures\DefaultNamedActionFixture;
use Sylament\Actions\Tests\Fixtures\StateFieldFixture;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;

it('exposes the name passed to make()', function (): void {
    expect(Action::make('delete')->getName())->toBe('delete');
});

it('throws when the name is blank', function (): void {
    expect(fn (): string => Action::make()->getName())->toThrow(LogicException::class);
});

it('round-trips the action closure', function (): void {
    $closure = fn (): string => 'ran';

    expect(Action::make('delete')->action($closure)->getAction())->toBe($closure);
});

it('round-trips a string action as a live-action click handler', function (): void {
    $action = Action::make('submit')->action('callMountedAction');

    expect($action->getAction())->toBe('callMountedAction')
        ->and($action->getClientClickHandler())->toBe('callMountedAction');
});

it('returns the string from call() on a string action', function (): void {
    expect(Action::make('submit')->action('callMountedAction')->call())->toBe('callMountedAction');
});

it('has no client click handler for closure actions', function (): void {
    expect(Action::make('delete')->action(fn (): null => null)->getClientClickHandler())->toBeNull()
        ->and(Action::make('delete')->getClientClickHandler())->toBeNull();
});

it('names callMountedAction as the live-component call target', function (): void {
    expect(Action::make('submit')->getLiveComponentCallMountedActionName())->toBe('callMountedAction');
});

it('does not require confirmation by default', function (): void {
    expect(Action::make('delete')->isConfirmationRequired())->toBeFalse();
});

it('requires confirmation once requested', function (): void {
    expect(Action::make('delete')->requiresConfirmation()->isConfirmationRequired())->toBeTrue();
});

it('calls the action closure, injecting the record and arguments', function (): void {
    $seen = null;
    $action = Action::make('delete')
        ->record('Ada')
        ->arguments(['soft' => true])
        ->action(function (mixed $record, array $arguments) use (&$seen): void {
            $seen = [$record, $arguments];
        });

    $action->call();

    expect($seen)->toBe(['Ada', ['soft' => true]]);
});

it('defaults the status to Success and flips to Failure on failure()', function (): void {
    expect(Action::make('delete')->getStatus())->toBe(ActionStatus::Success);

    $action = Action::make('delete');
    $action->failure();

    expect($action->getStatus())->toBe(ActionStatus::Failure);
});

it('throws Cancel from cancel() and Halt from halt()', function (): void {
    expect(fn (): mixed => Action::make('delete')->call())->not->toThrow(Halt::class);
    expect(fn () => Action::make('delete')->cancel())->toThrow(Cancel::class);
    expect(fn () => Action::make('delete')->halt())->toThrow(Halt::class);
});

it('returns an empty context without a schema binding', function (): void {
    expect(Action::make('save')->getContext())->toBe([]);
});

it('emits the schema container key as the schemaComponent context', function (): void {
    $action = Action::make('save')->schemaContainer(Schema::make()->key('form'));

    expect($action->getContext())->toBe(['schemaComponent' => 'form']);
});

it('strips the schemaComponent context once a parent action is set, even though the binding alone would emit it', function (): void {
    $action = Action::make('save')
        ->schemaContainer(Schema::make()->key('form'))
        ->parentAction(Action::make('edit'));

    expect($action->getContext())->toBe([]);
});

it('emits the schema component key as the schemaComponent context when only a component is bound', function (): void {
    $component = new Component()->key('box')->container(Schema::make()->key('form'));

    $action = Action::make('save')->schemaComponent($component);

    expect($action->getContext())->toBe(['schemaComponent' => 'form.box']);
});

it('returns an empty context when the bound schema container has no key', function (): void {
    $action = Action::make('save')->schemaContainer(Schema::make());

    expect($action->getContext())->toBe([]);
});

it('injects the schema container as $schema', function (): void {
    $schema = Schema::make();

    $action = Action::make('save')->schemaContainer($schema);

    expect($action->evaluate(fn ($schema): Schema => $schema))->toBe($schema);
});

it('injects the schema component as $component', function (): void {
    $section = Section::make('Box');

    $action = Action::make('save')->schemaComponent($section);

    expect($action->evaluate(fn ($component): Section => $component))->toBe($section);
});

it('injects $get and $set utilities scoped to the schema component', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada', 'subtitle' => 'Bo'];

    $title = StateFieldFixture::make('title');
    $subtitle = StateFieldFixture::make('subtitle');
    $schema = Schema::make($host)->statePath('data')->components([$title, $subtitle]);
    $schema->getComponents();

    $action = Action::make('save')->schemaComponent($subtitle);

    expect($action->evaluate(fn ($get): mixed => $get('title')))->toBe('Ada');

    $action->evaluate(function (Set $set): void {
        $set('title', 'Grace');
    });

    expect($host->data['title'])->toBe('Grace');
});

it('lets $get and $set reach into the bound schema component\'s own child schema, disabling the self-skip', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['address' => ['city' => 1]];

    $group = StateFieldFixture::make('address')->components([
        StateFieldFixture::make('city')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
    ]);
    Schema::make($host)->statePath('data')->components([$group])->getComponents();

    $action = Action::make('save')->schemaComponent($group);

    expect($action->evaluate(fn ($get): mixed => $get('/data.address.city')))->toBe(true);

    $action->evaluate(fn (Set $set): mixed => $set('/data.address.city', false));

    expect($host->data['address']['city'])->toBe(0);
});

it('injects $state from the nearest state-path-bearing parent component', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada'];

    $title = StateFieldFixture::make('title');
    Schema::make($host)->statePath('data')->components([$title])->getComponents();

    $inner = Schema::make($host)->parentComponent($title);
    $action = Action::make('save')->schemaContainer($inner);

    expect($action->evaluate(fn ($state): mixed => $state))->toBe('Ada');
});

it('injects $state from the schema component itself when no container is bound', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada'];

    $title = StateFieldFixture::make('title');
    Schema::make($host)->statePath('data')->components([$title])->getComponents();

    $action = Action::make('save')->schemaComponent($title);

    expect($action->evaluate(fn ($state): mixed => $state))->toBe('Ada');
});

it('injects $schemaState from the first state-path-bearing schema container', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada', 'subtitle' => 'Bo'];

    $schema = Schema::make($host)->statePath('data')->components([
        StateFieldFixture::make('title'),
        StateFieldFixture::make('subtitle'),
    ]);

    $action = Action::make('save')->schemaContainer($schema);

    expect($action->evaluate(fn ($schemaState): mixed => $schemaState))
        ->toBe(['title' => 'Ada', 'subtitle' => 'Bo']);
});

it('injects $data from getData()', function (): void {
    $action = Action::make('save')->data(['title' => 'Ada']);

    expect($action->evaluate(fn ($data): array => $data))->toBe(['title' => 'Ada']);
});

it('throws a named exception when $get is requested without a schema component', function (): void {
    expect(fn (): mixed => Action::make('save')->evaluate(fn (Get $get): mixed => $get('title')))
        ->toThrow(LogicException::class, 'has no schema component');
});

it('defaults to the button view', function (): void {
    $action = Action::make('delete');

    expect($action->getTemplate())->toBe(Action::BUTTON_TEMPLATE)
        ->and($action->isButton())->toBeTrue()
        ->and($action->isBadge())->toBeFalse()
        ->and($action->isIconButton())->toBeFalse()
        ->and($action->isLink())->toBeFalse();
});

it('switches to the badge view', function (): void {
    $action = Action::make('delete')->badge();

    expect($action->getTemplate())->toBe(Action::BADGE_TEMPLATE)
        ->and($action->isBadge())->toBeTrue()
        ->and($action->isButton())->toBeFalse();
});

it('switches to the button view', function (): void {
    expect(Action::make('delete')->link()->button()->getTemplate())->toBe(Action::BUTTON_TEMPLATE);
});

it('switches to the grouped view', function (): void {
    expect(Action::make('delete')->grouped()->getTemplate())->toBe(Action::GROUPED_TEMPLATE);
});

it('switches to the icon button view', function (): void {
    $action = Action::make('delete')->iconButton();

    expect($action->getTemplate())->toBe(Action::ICON_BUTTON_TEMPLATE)
        ->and($action->isIconButton())->toBeTrue()
        ->and($action->isButton())->toBeFalse();
});

it('switches to the link view', function (): void {
    $action = Action::make('delete')->link();

    expect($action->getTemplate())->toBe(Action::LINK_TEMPLATE)
        ->and($action->isLink())->toBeTrue()
        ->and($action->isButton())->toBeFalse();
});

it('keeps an explicit view over a pushed default template', function (): void {
    expect(Action::make('delete')->button()->defaultTemplate(Action::GROUPED_TEMPLATE)->getTemplate())
        ->toBe(Action::BUTTON_TEMPLATE);
});

it('round-trips the parent action', function (): void {
    $parent = Action::make('edit');
    $child = Action::make('confirm')->parentAction($parent);

    expect($child->getParentAction())->toBe($parent)
        ->and(Action::make('confirm')->getParentAction())->toBeNull();
});

it('falls back to getDefaultName() when make() is given no name', function (): void {
    expect(DefaultNamedActionFixture::make()->getName())->toBe('publish');
});

it('prefers an explicit name over getDefaultName()', function (): void {
    expect(DefaultNamedActionFixture::make('archive')->getName())->toBe('archive');
});

it('returns null from getDefaultName() by default', function (): void {
    expect(Action::getDefaultName())->toBeNull();
});
