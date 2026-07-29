<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Schemas\Schema;

function renderVariant(Action $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders a link action wired to mountAction', function (): void {
    $html = renderVariant(Action::make('delete')->link()->color('danger')->size('sm'));

    expect($html)->toContain('sy sy-link')
        ->toContain('sy-ac')
        ->toContain('sy-size-sm')
        ->toContain('sy-color-danger')
        ->toContain('type="button"')
        ->toContain('<span class="sy-link-label">Delete</span>')
        ->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"');
});

it('renders a url link action as an anchor without mount wiring', function (): void {
    $html = renderVariant(Action::make('edit')->link()->url('/users/1/edit')->openUrlInNewTab());

    expect($html)->toContain('<a')
        ->toContain('href="/users/1/edit"')
        ->toContain('target="_blank"')
        ->not->toContain('live#action')
        ->not->toContain('type=');
});

it('renders a hidden-label link action as an aria-label only', function (): void {
    $html = renderVariant(Action::make('delete')->link()->hiddenLabel()->icon('heroicons:x-mark'));

    expect($html)->toContain('aria-label="Delete"')
        ->toContain('<svg')
        ->not->toContain('sy-link-label');
});

it('renders a badge action', function (): void {
    $html = renderVariant(Action::make('delete')->badge()->color('danger')->icon('heroicons:x-mark'));

    expect($html)->toContain('sy sy-badge')
        ->toContain('sy-ac')
        ->toContain('sy-color-danger')
        ->toContain('<svg')
        ->toContain('<span class="sy-badge-label-ctn"><span class="sy-badge-label">Delete</span></span>')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"');
});

it('renders an icon button action composing the support IconButton', function (): void {
    $html = renderVariant(Action::make('delete')->iconButton()->icon('heroicons:x-mark'));

    expect($html)->toContain('sy-icon-btn')
        ->toContain('sy-ac')
        ->toContain('aria-label="Delete"')
        ->toContain('<svg')
        ->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"')
        ->not->toContain('Delete<');
});

it('emits invoked arguments on the icon button mount wiring', function (): void {
    $html = renderVariant(Action::make('delete')->iconButton()->icon('heroicons:x-mark')(['id' => 5]));

    expect($html)->toContain('data-live-arguments-param="{&quot;id&quot;:5}"');
});

it('renders a url icon button action as an anchor without mount wiring', function (): void {
    $html = renderVariant(Action::make('edit')->iconButton()->icon('heroicons:x-mark')->url('/users/1/edit'));

    expect($html)->toContain('<a')
        ->toContain('href="/users/1/edit"')
        ->not->toContain('live#action');
});

it('emits the schema mount context on schema-bound variant triggers', function (string $template): void {
    $schema = Schema::make()->key('schema')->components([
        Action::make('publish')->template($template),
    ]);

    $html = renderVariant($schema->getAction('publish'));

    expect($html)->toContain('data-live-context-param="{&quot;schemaComponent&quot;:&quot;schema&quot;}"');
})->with([
    'link' => Action::LINK_TEMPLATE,
    'badge' => Action::BADGE_TEMPLATE,
    'icon button' => Action::ICON_BUTTON_TEMPLATE,
]);

it('flattens extra attributes onto the icon-button root, merging class tokens', function (): void {
    $html = renderVariant(
        Action::make('delete')
            ->iconButton()
            ->icon('heroicons:x-mark')
            ->extraAttributes(['class' => 'custom-hook', 'data-x' => '1']),
    );

    expect($html)->toContain('data-x="1"')
        ->toContain('custom-hook')
        ->toContain('sy-ac')
        ->toContain('sy-icon-btn');
});

/*
 * The second assertion is the one that matters at runtime: a submit trigger's
 * click must reach the server through its form's submit event and nothing else.
 * `getModalSubmitAction()` gives a form-wrapped action `->submit(…)->action(null)`,
 * so without this suppression the trigger falls through its click cascade to the
 * `mountAction` default and every click fires a second live request — for the
 * modal submit button, `mountAction('submit')`, naming an action that does not
 * exist. Filament suppresses the same binding in `isLivewireClickHandlerEnabled()`.
 */
it('renders type="submit" and the wired form id for a submit-capable action, with no click binding', function (string $template): void {
    $html = renderVariant(
        Action::make('save')
            ->template($template)
            ->icon('heroicons:x-mark')
            ->submit('callMountedAction')
            ->formId('my-form'),
    );

    expect($html)->toContain('type="submit"')
        ->toContain('form="my-form"')
        ->not->toContain('live#action')
        ->not->toContain('data-live-action-param');
})->with([
    'button' => Action::BUTTON_TEMPLATE,
    'link' => Action::LINK_TEMPLATE,
    'badge' => Action::BADGE_TEMPLATE,
    'grouped' => Action::GROUPED_TEMPLATE,
    'icon button' => Action::ICON_BUTTON_TEMPLATE,
]);
