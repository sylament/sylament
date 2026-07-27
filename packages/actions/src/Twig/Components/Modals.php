<?php

declare(strict_types=1);

namespace Sylament\Actions\Twig\Components;

use LogicException;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\ComponentStack;

/**
 * Renders the modal of whatever action is currently mounted on the host.
 *
 * Filament's Blade counterpart reads `$this` because Blade components inherit the
 * Livewire component's scope; a Twig component gets a fresh context where `this`
 * is the component itself. The host is therefore resolved by walking the render
 * stack to the nearest `InteractsWithActions`, keeping the call site zero-argument
 * — `<twig:SylamentActions:Modals />` — wherever the tag is nested.
 */
#[AsTwigComponent(
    name: 'SylamentActions:Modals',
    template: '@SylamentActions/components/Modals.html.twig',
)]
class Modals
{
    private ?InteractsWithActions $liveComponent = null;

    public function __construct(private readonly ComponentStack $componentStack)
    {
    }

    public function mount(?InteractsWithActions $liveComponent = null): void
    {
        $this->liveComponent = $liveComponent;
    }

    public function getLiveComponent(): InteractsWithActions
    {
        if ($this->liveComponent instanceof InteractsWithActions) {
            return $this->liveComponent;
        }

        foreach ($this->componentStack as $mounted) {
            $component = $mounted->getComponent();

            if ($component instanceof InteractsWithActions) {
                return $component;
            }
        }

        throw new LogicException(sprintf('<twig:SylamentActions:Modals /> found no action host to render: place it inside a live component implementing "%s", or pass the host explicitly with :liveComponent="this".', InteractsWithActions::class));
    }
}
