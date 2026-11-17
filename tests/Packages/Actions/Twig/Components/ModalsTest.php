<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\ActionStackHost;
use Sylament\Tests\Fixtures\Actions\SchemaModalHost;
use Sylament\Tests\Fixtures\Actions\SectionedModalsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

it('renders a dialog per mounted frame of the host it is handed, in stack order and inside nothing, and nothing at all for an empty stack', function (): void {
    $host = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('details')->mountAction('notes')->component();
    $rendered = $this->renderTwigComponent('SylamentActions:Modals', ['liveComponent' => $host])->crawler();
    [$confirm, $details, $notes] = $rendered->filter('dialog')->each(static fn (Crawler $dialog): ?string => $dialog->attr('id'));

    expect($rendered->filter('dialog h2')->each(static fn (Crawler $heading): string => $heading->text()))->toBe(['Confirm the run', 'Details', 'Notes'])
        ->and($rendered->filter('body')->children()->each(static fn (Crawler $frame): ?string => $frame->attr('id')))
        ->toBe(["{$confirm}-w-confirm", "{$details}-w-details", "{$notes}-w-notes"])
        ->and(mb_trim($this->renderTwigComponent('SylamentActions:Modals', ['liveComponent' => $this->createLiveComponent(ActionStackHost::class)->component()])->toString()))->toBe('');
});

it('keeps a parent shut but rendered under a child with a modal of its own, and opens it again once the child unmounts', function (): void {
    $tester = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('details');
    $crawler = $tester->render()->crawler();

    expect($crawler->filter(Selectors::mountedModal(0) . ':not(' . Selectors::OPEN . '):not(' . SupportSelectors::modalValue('open') . ')')->count())->toBe(1)
        ->and($crawler->filter(Selectors::mountedModal(1) . Selectors::OPEN . SupportSelectors::modalValue('open', 'true'))->count())->toBe(1);

    $crawler = $tester->unmountAction()->render()->crawler();

    expect($crawler->filter(Selectors::mountedModal(0) . Selectors::OPEN . SupportSelectors::modalValue('open', 'true'))->count())->toBe(1)
        ->and($crawler->filter(Selectors::mountedModal(1))->count())->toBe(0);
});

it('marks an open parent under an overlaying child as overlaid, never the top frame or a parent its child shut', function (): void {
    $overlaid = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('overlay')->render()->crawler();
    $shut = $this->createLiveComponent(ActionStackHost::class)->mountAction('confirm')->mountAction('details')->render()->crawler();

    expect($overlaid->filter(Selectors::mountedModal(0) . Selectors::OPEN . SupportSelectors::MODAL_OVERLAID)->count())->toBe(1)
        ->and($overlaid->filter(Selectors::mountedModal(1) . Selectors::OPEN . ':not(' . SupportSelectors::MODAL_OVERLAID . ')')->count())->toBe(1)
        ->and($shut->filter(Selectors::mountedModal(0) . ':not(' . Selectors::OPEN . '):not(' . SupportSelectors::MODAL_OVERLAID . ')')->count())->toBe(1);
});

it('keeps the dialog id of a frame whose action replaces itself, renaming only its wrapper', function (): void {
    $tester = $this->createLiveComponent(SchemaModalHost::class)->mountAction('swap');
    $dialogId = $tester->render()->crawler()->filter(Selectors::mountedModal(0))->attr('id');

    expect($tester->render()->crawler()->filter("[id=\"{$dialogId}-w-swap\"]")->count())->toBe(1);

    $crawler = $tester->callMountedAction()->assertActionMounted('bare')->render()->crawler();

    expect($crawler->filter(Selectors::mountedModal(0) . Selectors::OPEN)->attr('id'))->toBe($dialogId)
        ->and($crawler->filter("[id=\"{$dialogId}-w-bare\"]")->count())->toBe(1)
        ->and($crawler->filter("[id=\"{$dialogId}-w-swap\"]")->count())->toBe(0);
});

it('finds its host through a component it is nested in', function (): void {
    expect($this->createLiveComponent(SectionedModalsHost::class)->mountAction('peek')->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN))
        ->toContainElement('h2', 'Peek');
});

it('refuses to render outside a host it is not handed, naming both ways to give it one', function (): void {
    expect(fn () => $this->renderTwigComponent('SylamentActions:Modals'))->toThrow(function (RuntimeError $error): void {
        expect($error->getPrevious())->toBeInstanceOf(LogicException::class)
            ->and($error->getPrevious()?->getMessage())->toContain('place it inside a live component implementing')->toContain(':liveComponent="this"');
    });
});
