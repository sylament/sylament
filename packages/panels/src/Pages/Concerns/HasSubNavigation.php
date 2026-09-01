<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages\Concerns;

use LogicException;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\Page as ResourcePage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;

use function Sylament\Support\filled;

trait HasSubNavigation
{
    protected static ?SubNavigationPosition $subNavigationPosition = null;

    /**
     * @var array<NavigationGroup>|null
     */
    private ?array $cachedSubNavigation = null;

    private ?CurrentPanel $currentPanel = null;

    private ?PanelRegistry $panels = null;

    private ?UrlGeneratorInterface $subNavigationUrlGenerator = null;

    #[Required]
    public function setSubNavigationServices(CurrentPanel $currentPanel, PanelRegistry $panels, UrlGeneratorInterface $urlGenerator): void
    {
        $this->currentPanel = $currentPanel;
        $this->panels = $panels;
        $this->subNavigationUrlGenerator = $urlGenerator;
    }

    /**
     * @return array<NavigationItem|NavigationGroup>
     */
    public function getSubNavigation(): array
    {
        return [];
    }

    /**
     * The band's own navigation, normalized to groups: every ungrouped item in one
     * label-less group first, then a group per label. Memoized for the render.
     *
     * @return array<NavigationGroup>
     */
    public function getCachedSubNavigation(): array
    {
        if ($this->cachedSubNavigation !== null) {
            return $this->cachedSubNavigation;
        }

        $items = [];
        $groups = [];

        foreach ($this->getSubNavigation() as $entry) {
            if ($entry instanceof NavigationGroup) {
                $groups[$entry->getLabel() ?? ''] = $entry;

                continue;
            }

            $items[] = $entry;
        }

        $ungroupedItems = [];

        foreach ($this->sortVisibleNavigationItems($items) as $item) {
            $group = $item->getGroup();

            if (! filled($group)) {
                $ungroupedItems[] = $item;

                continue;
            }

            $groups[$group] ??= NavigationGroup::make()->label($group);
            $groups[$group]->items([...$groups[$group]->getItems(), $item]);
        }

        foreach ($groups as $group) {
            $group
                ->items($this->sortVisibleNavigationItems($group->getItems()))
                ->collapsible(false);
        }

        return $this->cachedSubNavigation = [
            ...($ungroupedItems === [] ? [] : [NavigationGroup::make()->items($ungroupedItems)->collapsible(false)]),
            ...array_values($groups),
        ];
    }

    /**
     * The page static wins over the panel knob, which wins over `Start` — and a page
     * built outside the container, holding neither service, answers `Start`.
     */
    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return static::$subNavigationPosition
            ?? $this->getSubNavigationPanel()?->getSubNavigationPosition()
            ?? SubNavigationPosition::Start;
    }

    /**
     * @param array<class-string<Page>> $components
     *
     * @return array<NavigationItem>
     */
    public function generateNavigationItems(array $components): array
    {
        $parameters = $this->getSubNavigationParameters();

        $items = [];

        foreach ($components as $component) {
            if (! $component::shouldRegisterNavigation()) {
                continue;
            }

            if (is_subclass_of($component, ResourcePage::class)) {
                $items = [...$items, ...$this->generateResourcePageNavigationItems($component, $parameters)];

                continue;
            }

            $items[] = NavigationItem::make($component::getNavigationLabel())
                ->icon($component::getNavigationIcon())
                ->sort($component::getNavigationSort())
                ->badge($component::getNavigationBadge(), color: $component::getNavigationBadgeColor())
                ->url($this->generatePageNavigationUrl($component))
                ->isActiveWhen(fn (): bool => $component === static::class);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubNavigationParameters(): array
    {
        return [];
    }

    /**
     * @param class-string<Page>   $pageClass
     * @param array<string, mixed> $parameters
     *
     * @return array<NavigationItem>
     */
    protected function generateResourcePageNavigationItems(string $pageClass, array $parameters): array
    {
        throw new LogicException(sprintf(
            'The page [%s] lists the resource page [%s] in its sub-navigation, but resource sub-navigation lands with the record pages.',
            static::class,
            $pageClass,
        ));
    }

    /**
     * @param array<NavigationItem> $items
     *
     * @return array<NavigationItem>
     */
    private function sortVisibleNavigationItems(array $items): array
    {
        $items = array_values(array_filter($items, static fn (NavigationItem $item): bool => $item->isVisible()));

        usort($items, static fn (NavigationItem $a, NavigationItem $b): int => $a->getSort() <=> $b->getSort());

        return $items;
    }

    /**
     * @param class-string<Page> $component
     */
    private function generatePageNavigationUrl(string $component): string
    {
        $panel = $this->getSubNavigationPanel()
            ?? throw new LogicException(sprintf('The page [%s] builds sub-navigation outside any panel, so the route of [%s] cannot be generated.', static::class, $component));

        $urlGenerator = $this->subNavigationUrlGenerator
            ?? throw new LogicException(sprintf('The page [%s] was built outside the container, so it holds no URL generator; register it as an autowired service.', static::class));

        return $urlGenerator->generate($component::getRouteName($panel));
    }

    private function getSubNavigationPanel(): ?Panel
    {
        return $this->currentPanel?->get() ?? $this->panels?->getDefault();
    }
}
