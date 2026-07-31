<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Schemas\Schema;

function renderAction(Action $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders a styled trigger button', function (): void {
    $html = renderAction(Action::make('delete')->color('danger')->icon('heroicons:x-mark')->size('sm'));

    expect($html)->toContain('sy sy-btn sy-size-sm sy-color sy-color-danger sy-bg-color-600 hover:sy-bg-color-500 dark:sy-bg-color-600 dark:hover:sy-bg-color-500 sy-text-color-0 hover:sy-text-color-0 dark:sy-text-color-0 dark:hover:sy-text-color-0 sy-ac')
        ->toContain('<svg')
        ->toContain('type="button"')
        ->toContain('Delete');
});

it('wires the trigger button to the mountAction live action', function (): void {
    $html = renderAction(Action::make('delete'));

    expect($html)->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"');
});

it('emits the schema mount context on a schema-bound trigger', function (): void {
    $schema = Schema::make()->key('schema')->components([Action::make('publish')]);

    $html = renderAction($schema->getAction('publish'));

    expect($html)->toContain('data-live-context-param="{&quot;schemaComponent&quot;:&quot;schema&quot;}"');
});

it('emits the schema mount context on a schema-bound grouped trigger', function (): void {
    $schema = Schema::make()->key('schema')->components([
        Action::make('publish')->template(Action::GROUPED_TEMPLATE),
    ]);

    $html = renderAction($schema->getAction('publish'));

    expect($html)->toContain('sy-dropdown-item')
        ->toContain('data-live-context-param="{&quot;schemaComponent&quot;:&quot;schema&quot;}"');
});

it('emits no mount context on an unbound trigger', function (): void {
    expect(renderAction(Action::make('delete')))
        ->not->toContain('data-live-context-param');
});

it('emits invoked arguments as a mount param', function (): void {
    $html = renderAction(Action::make('delete')(['id' => 5]));

    expect($html)->toContain('data-live-arguments-param="{&quot;id&quot;:5}"');
});

it('emits no arguments mount param without invoked arguments', function (): void {
    expect(renderAction(Action::make('delete')))->not->toContain('data-live-arguments-param');
});

it('renders a url action as a link without mount wiring', function (): void {
    $html = renderAction(Action::make('edit')->url('/users/1/edit')->openUrlInNewTab());

    expect($html)->toContain('<a')
        ->toContain('href="/users/1/edit"')
        ->toContain('target="_blank"')
        ->not->toContain('live#action')
        ->not->toContain('type=');
});

it('renders an outlined button', function (): void {
    $html = renderAction(Action::make('delete')->outlined());

    expect($html)->toContain('class="sy sy-btn sy-outlined sy-size-md sy-color sy-color-primary sy-text-color-700 dark:sy-text-color-500 sy-ac"')
        ->not->toContain('sy-bg-color-');
});

it('does not render the grouped icon on a button', function (): void {
    $html = renderAction(Action::make('delete')->groupedIcon('heroicons:x-mark'));

    expect($html)->not->toContain('<svg');
});

it('renders a hidden label as an aria-label only', function (): void {
    $html = renderAction(Action::make('delete')->hiddenLabel()->icon('heroicons:x-mark'));

    expect($html)->toContain('aria-label="Delete"')
        ->toContain('<svg')
        ->not->toContain('Delete<');
});

it('wires a string action straight to its live action, skipping the mount round-trip', function (): void {
    $html = renderAction(Action::make('submit')->action('callMountedAction'));

    expect($html)->toContain('data-live-action-param="callMountedAction"')
        ->not->toContain('mountAction');
});

it('closes the modal client-side for a closing action', function (): void {
    $html = renderAction(Action::make('cancel')->close());

    expect($html)->toContain('data-action="sylament--support--modal#dismiss"')
        ->not->toContain('data-live-action-param="mountAction"')
        ->not->toContain('data-live-action-param="unmountAction"');
});

it('wires a parent-call action to the named handler with its arguments', function (): void {
    $html = renderAction(Action::make('save')->callParent('callMountedAction')->arguments(['key' => 'value']));

    expect($html)->toContain('data-live-action-param="callMountedAction"')
        ->toContain('data-live-arguments-param="{&quot;key&quot;:&quot;value&quot;}"')
        ->not->toContain('data-live-action-param="mountAction"');
});

it('wires a parent-call action on the icon-button variant', function (): void {
    $html = renderAction(
        Action::make('save')->iconButton()->icon('heroicons:x-mark')
            ->callParent('callMountedAction')->arguments(['key' => 'value']),
    );

    expect($html)->toContain('data-live-action-param="callMountedAction"')
        ->toContain('data-live-arguments-param="{&quot;key&quot;:&quot;value&quot;}"')
        ->not->toContain('data-live-action-param="mountAction"');
});

it('lets a parent-call action win over close() on both trigger shapes', function (): void {
    $button = renderAction(Action::make('save')->callParent('callMountedAction')->close());
    $iconButton = renderAction(
        Action::make('save')->iconButton()->icon('heroicons:x-mark')->callParent('callMountedAction')->close(),
    );

    expect($button)->toContain('data-live-action-param="callMountedAction"')
        ->not->toContain('sylament--support--modal#dismiss')
        ->and($iconButton)->toContain('data-live-action-param="callMountedAction"')
        ->not->toContain('sylament--support--modal#dismiss');
});

it('lets a string action win over close()', function (): void {
    $html = renderAction(Action::make('submit')->action('callMountedAction')->close());

    expect($html)->toContain('data-live-action-param="callMountedAction"')
        ->not->toContain('unmountAction');
});

it('wires a string action on the icon-button variant', function (): void {
    $html = renderAction(Action::make('submit')->iconButton()->icon('heroicons:x-mark')->action('callMountedAction'));

    expect($html)->toContain('data-live-action-param="callMountedAction"')
        ->not->toContain('mountAction');
});

it('closes the modal client-side for a closing icon-button', function (): void {
    $html = renderAction(Action::make('cancel')->iconButton()->icon('heroicons:x-mark')->close());

    expect($html)->toContain('data-action="sylament--support--modal#dismiss"')
        ->not->toContain('data-live-action-param="mountAction"')
        ->not->toContain('data-live-action-param="unmountAction"');
});

/*
 * The icon-button gates its close binding on the same `url or disabled` test as its mount
 * binding, so a click that navigates (or cannot happen at all) carries no dismiss. The
 * plain case is asserted alongside it: without it, deleting the binding outright would
 * satisfy the other two.
 */
it('drops the icon-button dismiss for a url or disabled closing action', function (): void {
    $icon = fn (Action $action): string => renderAction($action->iconButton()->icon('heroicons:x-mark')->close());

    expect($icon(Action::make('cancel')))->toContain('sylament--support--modal#dismiss')
        ->and($icon(Action::make('cancel')->url('/somewhere')))->not->toContain('sylament--support--modal#dismiss')
        ->and($icon(Action::make('cancel')->disabled()))->not->toContain('sylament--support--modal#dismiss');
});

it('renders a disabled button', function (): void {
    $html = renderAction(Action::make('delete')->disabled());

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});
