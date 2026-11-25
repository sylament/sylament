<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\SchemaDeclarationHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Component\DomCrawler\Crawler;

it('renders its content, its form and its content footer in that order', function (): void {
    $modal = $this->createLiveComponent(SchemaDeclarationHost::class)->mountAction('layered')->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

    expect($modal->filter('[data-slot], input')->each(static fn (Crawler $part): ?string => $part->attr('data-slot') ?? $part->attr('name')))->toBe(['content', 'title', 'footer']);
});

it('unmounts its frame when the browser dismisses its dialog, cancelling the parents its action names', function (): void {
    $wrapper = fn (Action $action): Crawler => $this->renderTwigComponent('SylamentActions:ActionModal', ['action' => $action, 'id' => 'archive-modal'])->crawler()->filter('[id="archive-modal-w-archive"]');
    $dismissal = '[data-action="sylament--support--modal:dismissed->live#action"]' . Selectors::liveParameter('action', 'unmountAction');

    expect($wrapper(Action::make('archive')))->toContainElement($dismissal . Selectors::liveParameter('cancel-parent-actions', 'false') . ' dialog#archive-modal')
        ->and($wrapper(Action::make('archive')->cancelParentActionsOnClose('details')))->toContainElement($dismissal . Selectors::liveParameter('cancel-parent-actions', 'details') . ' dialog#archive-modal');
});

it('lists above its form the violations that name none of its fields, keeping the modal open, and lists nothing without them', function (): void {
    $this->seed(new CappedHeadline(code: 'H1', title: 'One', slug: 'taken'));

    $tester = $this->createLiveComponent(SchemaDeclarationHost::class)->mountAction('retitle');
    $modal = $tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

    expect($modal)->toContainElement('input[name="title"]')
        ->and($modal->filter(Selectors::RECORD_ERRORS)->count())->toBe(0);

    $modal = $tester->fillForm(['code' => 'H2', 'title' => 'Two'])->callMountedAction()->assertActionMounted('retitle')
        ->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

    expect($modal->filter(Selectors::RECORD_ERROR)->each(static fn (Crawler $error): string => $error->text()))->toBe(['This value is already used.'])
        ->and($modal->filter(Selectors::RECORD_ERRORS . ', input')->each(static fn (Crawler $part): string => $part->nodeName()))->toBe(['ul', 'input', 'input'])
        ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
});
