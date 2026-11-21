<?php

declare(strict_types=1);

namespace Sylament\Panels\Twig\Components;

use Sylament\Panels\CurrentPanel;
use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

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
        private readonly TranslatorInterface $translator,
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

        $results = $this->providers->get($providerClass)->getResults($search);

        if (! $results instanceof GlobalSearchResults) {
            return null;
        }

        $translated = GlobalSearchResults::make();

        foreach ($results->getCategories() as $category => $categoryResults) {
            $translated->category($category, array_map($this->translateResult(...), $categoryResults));
        }

        return $translated;
    }

    /**
     * A result is built with `new` by a resource or a provider of the application's own, so no translator sits on it:
     * what it holds is translated here, where the component reads it.
     */
    private function translateResult(GlobalSearchResult $result): GlobalSearchResult
    {
        return new GlobalSearchResult(
            title: $this->translate($result->title),
            url: $result->url,
            details: array_map(fn (string|TranslatableInterface $detail): string => $detail instanceof TranslatableInterface ? $detail->trans($this->translator) : $detail, $result->details),
            actions: $result->actions,
        );
    }

    private function translate(string|Markup|TranslatableInterface $text): string|Markup
    {
        return $text instanceof TranslatableInterface ? $text->trans($this->translator) : $text;
    }
}
