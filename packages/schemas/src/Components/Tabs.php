<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use LogicException;
use Sylament\Schemas\Components\Concerns\CanPersistTab;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Sylament\Support\Components\Concerns\CanBeContained;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Twig\Extension\IconExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class Tabs extends Component
{
    use CanBeContained;
    use CanPersistTab;
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Tabs/index.html.twig';

    protected int|Closure $activeTab = 1;

    protected string|Closure|null $tabQueryStringKey = null;

    protected string|Closure|null $liveComponentProperty = null;

    protected bool|Closure $isScrollable = true;

    protected bool|Closure $isVertical = false;

    protected ?IconExtension $icons = null;

    protected ?ColorManager $colors = null;

    protected ?RequestStack $requestStack = null;

    public static function make(string|Markup|Closure|null $label = null): static
    {
        $tabs = static::newInstance();

        $tabs->label($label);

        $tabs->configure();

        return $tabs;
    }

    #[Required]
    public function setEvaluationServices(IconExtension $icons, ColorManager $colors, ?RequestStack $requestStack = null): void
    {
        $this->icons = $icons;
        $this->colors = $colors;
        $this->requestStack = $requestStack;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key(function (Tabs $component): ?string {
            $label = $component->getLabel();

            if (blank($label)) {
                return null;
            }

            $statePath = $component->getStatePath();

            return new AsciiSlugger()->slug((string) $label)->lower()->toString() . '::' . (filled($statePath) ? "{$statePath}::tabs" : 'tabs');
        }, isInheritable: false);
    }

    /**
     * @param array<Tab>|Closure $tabs
     */
    public function tabs(array|Closure $tabs): static
    {
        $this->components($tabs);

        return $this;
    }

    public function activeTab(int|Closure $activeTab = 1): static
    {
        $this->activeTab = $activeTab;

        return $this;
    }

    public function persistTabInQueryString(string|Closure|null $key = 'tab'): static
    {
        $this->tabQueryStringKey = $key;

        return $this;
    }

    public function getTabQueryStringKey(): ?string
    {
        return $this->evaluate($this->tabQueryStringKey);
    }

    public function isTabPersistedInQueryString(): bool
    {
        return filled($this->getTabQueryStringKey());
    }

    public function liveComponentProperty(string|Closure|null $property): static
    {
        $this->liveComponentProperty = $property;

        return $this;
    }

    public function getLiveComponentProperty(): ?string
    {
        return $this->evaluate($this->liveComponentProperty);
    }

    public function scrollable(bool|Closure $condition = true): static
    {
        $this->isScrollable = $condition;

        return $this;
    }

    public function isScrollable(): bool
    {
        return $this->evaluate($this->isScrollable);
    }

    public function vertical(bool|Closure $condition = true): static
    {
        $this->isVertical = $condition;

        return $this;
    }

    public function isVertical(): bool
    {
        return $this->evaluate($this->isVertical);
    }

    /**
     * The active tab's 1-based position among the visible tabs: the query string's
     * tab when persisted there and the request carries a key a tab answers to,
     * otherwise whatever {@see activeTab()} was set to.
     */
    public function getActiveTab(): int
    {
        if ($this->isTabPersistedInQueryString()) {
            $queryStringTab = $this->requestStack?->getMainRequest()?->query->get((string) $this->getTabQueryStringKey());

            if (is_string($queryStringTab)) {
                foreach ($this->getTabs() as $index => $tab) {
                    if ($tab->getKey(isAbsolute: false) === $queryStringTab) {
                        return $index + 1;
                    }
                }
            }
        }

        return $this->evaluate($this->activeTab);
    }

    /**
     * The active tab's own non-absolute key — the value `data-tab-key` carries and the
     * client controller switches on. Sylament-only: Filament reads the active tab in
     * Alpine, which has no equivalent server-side accessor.
     */
    public function getActiveTabKey(): ?string
    {
        return ($this->getTabs()[$this->getActiveTab() - 1] ?? null)?->getKey(isAbsolute: false);
    }

    /**
     * The host property's value, as the string a tab key is compared against, in
     * {@see liveComponentProperty()} mode — null when the mode is off or the component
     * is unbound. Sylament-only: Filament reads the property in Alpine.
     */
    public function getActiveLiveComponentTab(): ?string
    {
        $property = $this->getLiveComponentProperty();
        $liveComponent = $this->getLiveComponentOrNull();

        if (blank($property) || !$liveComponent instanceof InteractsWithSchemas) {
            return null;
        }

        $value = $liveComponent->{$property} ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return list<Tab> the visible tabs, in order
     */
    public function getTabs(): array
    {
        return array_values(array_filter(
            $this->getChildSchema()?->getComponents(withHidden: false) ?? [],
            static fn (object $component): bool => $component instanceof Tab,
        ));
    }

    /**
     * @return array<string, array{badge: ?string, badgeColorClasses: string, badgeColorStyles: string, badgeIconHtml: ?string, badgeIconPosition: ?string, badgeTooltip: ?string}>
     */
    #[ExposedLiveComponentMethod]
    public function getDeferredTabBadges(): array
    {
        $icons = $this->icons;
        $colors = $this->colors;

        if (!$icons instanceof IconExtension || !$colors instanceof ColorManager) {
            throw new LogicException(sprintf(
                'A [%s] built outside the container cannot resolve deferred tab badges: no [%s] and [%s] were injected.',
                static::class,
                IconExtension::class,
                ColorManager::class,
            ));
        }

        $badges = [];

        foreach ($this->getTabs() as $tab) {
            if (!$tab->isBadgeDeferred()) {
                continue;
            }

            $badge = $tab->getBadge();
            $badgeColor = $tab->getBadgeColor($badge) ?? 'primary';
            $badgeColorClasses = '';
            $badgeColorStyles = '';

            if (is_array($badgeColor)) {
                $badgeColorClasses = 'sy-color';
                $badgeColorStyles = implode('; ', $colors->getComponentCustomStyles(BadgeComponent::class, $badgeColor));
            } else {
                $badgeColorClasses = implode(' ', $colors->getComponentClasses(BadgeComponent::class, $badgeColor));
            }

            $badgeIcon = $tab->getBadgeIcon($badge);
            $badgeIconPosition = $tab->getBadgeIconPosition($badge);
            $badgeTooltip = $tab->getBadgeTooltip($badge);

            $badges[(string) $tab->getKey(isAbsolute: false)] = [
                'badge' => $badge,
                'badgeColorClasses' => $badgeColorClasses,
                'badgeColorStyles' => $badgeColorStyles,
                'badgeIconHtml' => $badgeIcon ? (string) $icons->generateIconHtml($badgeIcon, size: IconSize::Small) : null,
                'badgeIconPosition' => $badgeIconPosition instanceof IconPosition ? $badgeIconPosition->value : $badgeIconPosition,
                'badgeTooltip' => $badgeTooltip !== null ? (string) $badgeTooltip : null,
            ];
        }

        return $badges;
    }

    public function hasDeferredBadges(): bool
    {
        return array_any($this->getTabs(), fn (Tab $tab): bool => $tab->isBadgeDeferred());
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $liveComponent = $this->getLiveComponentOrNull();

        return [
            'tabs' => $this->getTabs(),
            'activeTab' => $this->getActiveTab(),
            'activeTabKey' => $this->getActiveTabKey(),
            'tabsKey' => $this->getKey(),
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'isContained' => $this->isContained(),
            'isScrollable' => $this->isScrollable(),
            'isVertical' => $this->isVertical(),
            'isTabPersisted' => $this->isTabPersisted(),
            'tabQueryStringKey' => $this->getTabQueryStringKey(),
            'hasDeferredBadges' => $this->hasDeferredBadges(),
            'liveComponentProperty' => $this->getLiveComponentProperty(),
            'activeLiveComponentTab' => $this->getActiveLiveComponentTab(),
            'canGenerateTabLabel' => $liveComponent instanceof InteractsWithSchemas && method_exists($liveComponent, 'generateTabLabel'),
            'schemaKey' => $this->getRootContainer()->getKey(),
        ];
    }

    /**
     * Each tab's key must match the array key written into the live component property,
     * so a `live#update` write activates it. Done here rather than at render time so the
     * key is settled before any absolute key (a nested action's, say) is computed and cached.
     */
    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if ($key !== 'default' || blank($this->getLiveComponentProperty())) {
            return $schema;
        }

        $components = $this->evaluate($this->childComponents['default'] ?? []);

        if (!is_array($components)) {
            return $schema;
        }

        foreach ($components as $tabKey => $tab) {
            if ($tab instanceof Tab) {
                $tab->key((string) $tabKey, isInheritable: false);
            }
        }

        return $schema;
    }
}
