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

/**
 * The opening `<dialog …>` tag of every rendered stack frame, keyed by its nesting
 * index. Counting markers across the whole document cannot say *which* frame carries
 * one, so the per-frame assertions below slice the document per dialog first.
 *
 * @return array<int, string>
 */
function dialogTagsByNestingIndex(string $html): array
{
    preg_match_all('/<dialog\b[^>]*>/', $html, $matches);

    $tags = [];

    foreach ($matches[0] as $tag) {
        if (preg_match('/id="sy-[0-9a-f]{8}-action-(\d+)"/', $tag, $idMatch) === 1) {
            $tags[(int) $idMatch[1]] = $tag;
        }
    }

    return $tags;
}

it('renders the mounted action modal from a zero-argument tag', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)
        ->toContain('<dialog')
        ->toMatch('/id="sy-[0-9a-f]{8}-action-0"/')
        ->toContain('type="submit"')
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
        ->toMatch('/id="sy-[0-9a-f]{8}-action-0"/')
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
        ->toMatch('/id="sy-[0-9a-f]{8}-action-0"/');
});

it('throws when no action host is in scope', function (): void {
    expect(fn (): string => renderModals('<twig:SylamentActions:Modals />'))
        ->toThrow(RuntimeError::class, 'found no action host');
});

it('renders one modal per mounted frame with per-frame ids', function (): void {
    $component = $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'confirmDelete', 'arguments' => [], 'context' => []]);

    $html = (string) $component->render();

    expect(mb_substr_count($html, '<dialog'))->toBe(2)
        ->and($html)->toMatch('/id="sy-[0-9a-f]{8}-action-0"/')
        ->and($html)->toMatch('/id="sy-[0-9a-f]{8}-action-1"/');
});

it('closes the parent frame while a non-overlaying child is open', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'confirmDelete', 'arguments' => [], 'context' => []])
        ->render();

    $dialogs = dialogTagsByNestingIndex($html);

    expect(mb_substr_count($html, 'data-sylament--support--modal-open-value="true"'))->toBe(1)
        ->and($html)->not->toContain('sy-modal-overlaid')
        ->and($dialogs)->toHaveCount(2)
        ->and($dialogs[0])->not->toContain('data-sylament--support--modal-open-value="true"')
        ->and($dialogs[1])->toContain('data-sylament--support--modal-open-value="true"');
});

it('keeps the parent open and overlaid under an overlaying child', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'overlayChild', 'arguments' => [], 'context' => []])
        ->render();

    $dialogs = dialogTagsByNestingIndex($html);

    expect(mb_substr_count($html, 'data-sylament--support--modal-open-value="true"'))->toBe(2)
        ->and(mb_substr_count($html, 'sy-modal-overlaid'))->toBe(1)
        ->and($dialogs)->toHaveCount(2)
        ->and($dialogs[0])->toContain('data-sylament--support--modal-open-value="true"')
        ->and($dialogs[0])->toContain('sy-modal-overlaid')
        ->and($dialogs[1])->toContain('data-sylament--support--modal-open-value="true"')
        ->and($dialogs[1])->not->toContain('sy-modal-overlaid');
});

/*
 * The render-side half of `it keeps a parent open when a non-modal child halts on top
 * of it` (HasActionsTest): haltingChild opens no modal and halts, so it stays on the
 * stack covering nothing. The loop must render no dialog for it — without the top-frame
 * gate it would paint an empty, headless one over the parent.
 */
it('renders no dialog for a modal-less frame on top of the stack', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'haltingChild', 'arguments' => [], 'context' => []])
        ->render();

    $dialogs = dialogTagsByNestingIndex($html);

    expect($dialogs)->toHaveCount(1)
        ->and($dialogs)->toHaveKey(0)
        ->and($dialogs[0])->toContain('data-sylament--support--modal-open-value="true"');
});

it('carries the cancel-on-close value on each frame dismissal binding', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('sylament--support--modal:dismissed->live#action')
        ->toContain('data-live-cancel-parent-actions-param="false"');
});

/*
 * `deeper` declares cancelParentActionsOnClose('edit'), `edit` declares nothing. Both
 * values have to reach their own wrapper: a binding hardcoded to either one satisfies a
 * single-frame test, so the two frames are asserted to disagree.
 */
it('reads the cancel-on-close value per frame rather than sharing one', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->call('mountAction', ['name' => 'deeper', 'arguments' => [], 'context' => []])
        ->render();

    expect(mb_substr_count($html, 'sylament--support--modal:dismissed->live#action'))->toBe(2)
        ->and(mb_substr_count($html, 'data-live-cancel-parent-actions-param="false"'))->toBe(1)
        ->and(mb_substr_count($html, 'data-live-cancel-parent-actions-param="edit"'))->toBe(1);
});

it('closes the modal client-side for a cancel action instead of unmounting directly', function (): void {
    $html = (string) $this->createLiveComponent('ActionHost')
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->render();

    // Two dismiss bindings: the header close button (pre-existing) and the footer cancel
    // button (this change). Counting pins the cancel button — a bare toContain() would
    // pass on the close button alone even if cancel lost its click wiring entirely.
    expect($html)->toContain('sylament--support--modal#dismiss')
        ->and(mb_substr_count($html, 'data-action="sylament--support--modal#dismiss"'))->toBe(2)
        ->and(mb_substr_count($html, 'data-live-action-param="unmountAction"'))->toBe(1);
});
