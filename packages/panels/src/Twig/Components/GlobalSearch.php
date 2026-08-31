<?php

declare(strict_types=1);

namespace Sylament\Panels\Twig\Components;

use Sylament\Panels\CurrentPanel;
use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'SylamentPanels:GlobalSearch',
    template: '@SylamentPanels/components/GlobalSearch/index.html.twig',
)]
final class GlobalSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $search = '';

    #[LiveProp]
    public string $panelId = '';

    public function __construct(
        private readonly CurrentPanel $currentPanel,
        private readonly PanelRegistry $panels,
        private readonly GlobalSearchProviderRegistry $providers,
        private readonly RequestStack $requests,
    ) {
    }

    public function mount(): void
    {
        $this->panelId = ($this->currentPanel->get() ?? $this->panels->getDefault())->getId();
    }

    /**
     * A live request carries no `_sylament_panel` route default, so the prop
     * restores it — `CurrentPanel` then resolves for the provider and for every
     * `Resource::getUrl()` call underneath it.
     */
    #[PostHydrate]
    public function stampPanelRequestAttribute(): void
    {
        $this->requests->getCurrentRequest()?->attributes->set('_sylament_panel', $this->panelId);
    }

    public function getPanel(): Panel
    {
        return $this->panels->get($this->panelId) ?? $this->panels->getDefault();
    }

    /**
     * `null` is "nothing was searched for", which is what keeps the results
     * panel out of the markup entirely — an empty `GlobalSearchResults` is a
     * search that found nothing and renders the no-results message.
     */
    public function getResults(): ?GlobalSearchResults
    {
        $search = mb_trim($this->search ?? '');

        if ($search === '') {
            return null;
        }

        $providerClass = $this->getPanel()->getGlobalSearchProviderClass();

        if ($providerClass === null) {
            return null;
        }

        return $this->providers->get($providerClass)->getResults($search);
    }
}
