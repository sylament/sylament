<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\RenderActionHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Twig\Error\RuntimeError;

describe('renderAction()', function (): void {
    it('renders the trigger of the action the surrounding host resolves', function (): void {
        expect($this->createLiveComponent(RenderActionHost::class)->render()->crawler()->filter('[data-slot="plain"]'))
            ->toContainButtons(Selectors::TRIGGER . Selectors::LIVE_CLICK . Selectors::liveParameter('name', 'delete'), ['Delete']);
    });

    it('renders nothing for a name the host does not resolve, or for a hidden action', function (): void {
        $render = $this->createLiveComponent(RenderActionHost::class)->render()->crawler();

        expect($render->filter('[data-slot="plain"] ' . Selectors::TRIGGER))->toHaveCount(1)
            ->and($render->filter('[data-slot="missing"]'))->toHaveCount(1)
            ->and($render->filter('[data-slot="missing"] ' . Selectors::TRIGGER))->toHaveCount(0)
            ->and($render->filter('[data-slot="hidden"]'))->toHaveCount(1)
            ->and($render->filter('[data-slot="hidden"] ' . Selectors::TRIGGER))->toHaveCount(0);
    });

    it("mounts the trigger with the arguments it binds, leaving the host's own action without them", function (): void {
        $render = $this->createLiveComponent(RenderActionHost::class)->render()->crawler();

        expect($render->filter('[data-slot="bound"]'))->toContainElement(Selectors::LIVE_CLICK . Selectors::liveParameter('name', 'delete') . Selectors::liveParameter('arguments', '{"id":5}'), 'Delete')
            ->and($render->filter('[data-slot="plain"] ' . Selectors::TRIGGER))->toHaveCount(1)
            ->and($render->filter('[data-slot="plain"] ' . Selectors::liveParameter('arguments')))->toHaveCount(0);
    });

    it('checks visibility after binding, so a visibility closure reads the bound arguments', function (): void {
        $render = $this->createLiveComponent(RenderActionHost::class)->render()->crawler();

        expect($render->filter('[data-slot="scoped-bound"]'))->toContainButtons(Selectors::TRIGGER, ['Scoped'])
            ->and($render->filter('[data-slot="scoped"]'))->toHaveCount(1)
            ->and($render->filter('[data-slot="scoped"] ' . Selectors::TRIGGER))->toHaveCount(0);
    });

    it('refuses a template whose this is no actions host', function (): void {
        expect(fn () => $this::getContainer()->get('twig')->createTemplate("{{ renderAction('delete') }}")->render([]))
            ->toThrow(RuntimeError::class, 'Component must be an instance of Sylament\Actions\Contracts\InteractsWithActions');
    });
});
