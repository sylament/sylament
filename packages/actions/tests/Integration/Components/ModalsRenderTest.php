<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionHost;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Twig\Error\RuntimeError;

uses(InteractsWithLiveComponents::class);

/*
 * Covers `<twig:SylamentActions:Modals />`, the host-side renderer that replaces the
 * hand-rolled confirm-modal include every host template used to carry. The nesting
 * case is the one that justifies resolving the host from the render stack: an
 * enclosing Twig component would shadow `this` for any context-based approach.
 */

/**
 * @param array<string, mixed> $context
 */
function renderModals(string $template, array $context = []): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render($context);
}

it('renders the mounted action modal from a zero-argument tag', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)
        ->toContain('<dialog')
        ->toContain('id="delete-modal"')
        ->toContain('data-live-action-param="callMountedAction"')
        ->toContain('data-live-action-param="unmountAction"');
});

it('marks the mounted modal open in the markup rather than relying on event timing', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->render();

    // The `sylament-open-modal` event is dispatched in the same response that first
    // renders this dialog, and Stimulus binds listeners asynchronously — so the event
    // alone cannot open a freshly-inserted modal. The open state has to be in the markup.
    expect($html)->toContain('data-sylament--support--modal-open-value="true"');
});

it('resolves the host through an intervening Twig component', function (): void {
    $html = (string) $this->createLiveComponent('NestedActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)
        ->toContain('sy-section')
        ->toContain('<dialog')
        ->toContain('id="delete-modal"')
        ->toContain('Delete?');
});

it('renders nothing when no action is mounted', function (): void {
    expect((string) $this->createLiveComponent('ActionHost')->render())
        ->not->toContain('sy-modal');
});

it('renders nothing for a mounted action that does not open a modal', function (): void {
    $host = new ActionHost();
    $host->mountedActions = [['name' => 'notify', 'arguments' => [], 'context' => []]];

    expect(mb_trim(renderModals('<twig:SylamentActions:Modals :liveComponent="host" />', ['host' => $host])))
        ->toBe('');
});

it('renders the modal of an explicitly passed host', function (): void {
    $host = new ActionHost();
    $host->mountedActions = [['name' => 'delete', 'arguments' => [], 'context' => []]];

    expect(renderModals('<twig:SylamentActions:Modals :liveComponent="host" />', ['host' => $host]))
        ->toContain('<dialog')
        ->toContain('id="delete-modal"');
});

it('throws when no action host is in scope', function (): void {
    expect(fn (): string => renderModals('<twig:SylamentActions:Modals />'))
        ->toThrow(RuntimeError::class, 'found no action host');
});
