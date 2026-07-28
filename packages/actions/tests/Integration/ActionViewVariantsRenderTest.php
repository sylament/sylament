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

    expect($html)->toContain('sy sy-ac-link sy-ac')
        ->toContain('sy-size-sm')
        ->toContain('sy-color-danger')
        ->toContain('type="button"')
        ->toContain('Delete')
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
        ->not->toContain('Delete<');
});

it('renders a badge action', function (): void {
    $html = renderVariant(Action::make('delete')->badge()->color('danger')->icon('heroicons:x-mark'));

    expect($html)->toContain('sy sy-ac-badge sy-ac')
        ->toContain('sy-color-danger')
        ->toContain('<svg')
        ->toContain('<span class="sy-ac-badge-label">Delete</span>')
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
