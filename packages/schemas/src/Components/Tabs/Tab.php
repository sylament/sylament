<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Tabs;

use BackedEnum;
use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Contracts\CanConcealComponents;
use Sylament\Schemas\Components\Tabs;
use Sylament\Support\Components\Concerns\HasBadge;
use Sylament\Support\Components\Concerns\HasBadgeTooltip;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconPosition;
use Sylament\Support\Enums\IconPosition;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Markup;

use function Sylament\Support\filled;

class Tab extends Component implements CanConcealComponents
{
    use HasBadge;
    use HasBadgeTooltip;
    use HasIcon;
    use HasIconPosition;
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Tab/index.html.twig';

    protected ?Closure $modifyQueryUsing = null;

    protected bool|Closure $shouldExcludeQueryWhenResolvingRecord = false;

    protected string|BackedEnum|Markup|Closure|null $badgeIcon = null;

    protected IconPosition|string|Closure|null $badgeIconPosition = null;

    protected bool|Closure $isBadgeDeferred = false;

    public static function make(string|Markup|Closure|null $label = null): static
    {
        $tab = static::newInstance();

        $tab->label($label);

        $tab->configure();

        return $tab;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key(function (Tab $component): string {
            $label = $component->getLabel();
            $statePath = $component->getStatePath();

            return new AsciiSlugger()->slug((string) $label)->lower()->toString() . '::' . (filled($statePath) ? "{$statePath}::tab" : 'tab');
        }, isInheritable: false);
    }

    /**
     * @return array<string, ?int>
     */
    public function getAllColumns(): array
    {
        if ($this->columns === null) {
            return $this->getContainer()->getAllColumns();
        }

        return parent::getAllColumns();
    }

    public function canConcealComponents(): bool
    {
        return true;
    }

    public function query(?Closure $callback): static
    {
        $this->modifyQueryUsing($callback);

        return $this;
    }

    public function modifyQueryUsing(?Closure $callback): static
    {
        $this->modifyQueryUsing = $callback;

        return $this;
    }

    /**
     * The builder is mutated in place; the callback's return value is discarded.
     */
    public function modifyQuery(QueryBuilder $query): QueryBuilder
    {
        if (!$this->modifyQueryUsing instanceof Closure) {
            return $query;
        }

        $this->evaluate($this->modifyQueryUsing, [
            'query' => $query,
        ]);

        return $query;
    }

    public function excludeQueryWhenResolvingRecord(bool|Closure $condition = true): static
    {
        // Security: do NOT use this on tabs enforcing an authorization scope (restricting
        // records by tenant or by ownership). Excluding the query allows direct URL access
        // to records the tab's scope would otherwise keep out.
        $this->shouldExcludeQueryWhenResolvingRecord = $condition;

        return $this;
    }

    public function shouldExcludeQueryWhenResolvingRecord(): bool
    {
        return $this->evaluate($this->shouldExcludeQueryWhenResolvingRecord);
    }

    public function badgeIcon(string|BackedEnum|Markup|Closure|null $icon): static
    {
        $this->badgeIcon = $icon;

        return $this;
    }

    public function badgeIconPosition(IconPosition|string|Closure|null $position): static
    {
        $this->badgeIconPosition = $position;

        return $this;
    }

    public function getBadgeIcon(?string $badge = null): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->badgeIcon, [
            'badge' => $badge,
        ]);
    }

    public function getBadgeIconPosition(?string $badge = null): IconPosition|string
    {
        return $this->evaluate($this->badgeIconPosition, [
            'badge' => $badge,
        ]) ?? IconPosition::Before;
    }

    public function deferBadge(bool|Closure $condition = true): static
    {
        $this->isBadgeDeferred = $condition;

        return $this;
    }

    public function isBadgeDeferred(): bool
    {
        return $this->evaluate($this->isBadgeDeferred);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $tabs = $this->getContainer()->getParentComponent();

        if (!$tabs instanceof Tabs) {
            throw new LogicException(sprintf('A [%s] must be a child of a [%s] component.', static::class, Tabs::class));
        }

        $key = $this->getKey(isAbsolute: false);
        $liveComponentProperty = $tabs->getLiveComponentProperty();
        $contentSchema = $this->getChildSchema();

        return [
            'tabKey' => $key,
            'tabId' => $this->getId(),
            'isActive' => filled($liveComponentProperty)
                ? $tabs->getActiveLiveComponentTab() === (string) $key
                : $tabs->getActiveTabKey() === $key,
            'isLiveComponentProperty' => filled($liveComponentProperty),
            'contentSchema' => $contentSchema,
            'hasContent' => ($contentSchema?->getComponents(withHidden: false) ?? []) !== [],
        ];
    }
}
