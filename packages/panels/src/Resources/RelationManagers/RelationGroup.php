<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\RelationManagers;

use Closure;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Support\Components\Component;
use Sylament\Support\Components\Concerns\HasBadge;
use Sylament\Support\Components\Concerns\HasBadgeTooltip;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconPosition;
use Sylament\Support\Evaluation\ClosureParameter;

/**
 * Several relation managers behind one tab: the group supplies the tab's label
 * and chrome, and the page mounts every manager it holds inside that tab.
 */
class RelationGroup extends Component
{
    use HasBadge;
    use HasBadgeTooltip;
    use HasIcon;
    use HasIconPosition;

    protected string|Closure $label;

    /**
     * @var array<class-string<RelationManager>|RelationManagerConfiguration>|Closure
     */
    protected array|Closure $managers = [];

    protected ?object $ownerRecord = null;

    /**
     * @var class-string<Page>|null
     */
    protected ?string $pageClass = null;

    /**
     * The tab the callback answers, typed into the union so `evaluate()` infers
     * it — the same shape tables' query scopes carry.
     */
    protected Tab|Closure|null $modifyTabUsing = null;

    /**
     * @param array<class-string<RelationManager>|RelationManagerConfiguration>|Closure $managers
     */
    public static function make(string|Closure $label, array|Closure $managers = []): static
    {
        $relationGroup = static::newInstance();

        $relationGroup->label($label);
        $relationGroup->managers($managers);

        $relationGroup->configure();

        return $relationGroup;
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param array<class-string<RelationManager>|RelationManagerConfiguration>|Closure $managers
     */
    public function managers(array|Closure $managers): static
    {
        $this->managers = $managers;

        return $this;
    }

    public function ownerRecord(?object $record): static
    {
        $this->ownerRecord = $record;

        return $this;
    }

    /**
     * @param class-string<Page>|null $class
     */
    public function pageClass(?string $class): static
    {
        $this->pageClass = $class;

        return $this;
    }

    public function tab(?Closure $callback): static
    {
        $this->modifyTabUsing = $callback;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->evaluate($this->label);
    }

    /**
     * The managers this group shows, filtered by each one's own access answer
     * once the owner and the page are both known.
     *
     * @return array<class-string<RelationManager>|RelationManagerConfiguration>
     */
    public function getManagers(): array
    {
        $managers = $this->evaluate($this->managers);

        $ownerRecord = $this->getOwnerRecord();
        $pageClass = $this->getPageClass();

        if ($ownerRecord === null || $pageClass === null) {
            return $managers;
        }

        return array_filter(
            $managers,
            fn (string|RelationManagerConfiguration $manager): bool => $this->normalizeRelationManagerClass($manager)::canViewForRecord($ownerRecord, $pageClass),
        );
    }

    public function getOwnerRecord(): ?object
    {
        return $this->ownerRecord;
    }

    /**
     * @return class-string<Page>|null
     */
    public function getPageClass(): ?string
    {
        return $this->pageClass;
    }

    public function getTabComponent(): Tab
    {
        $badge = $this->getBadge();

        $tab = Tab::make($this->getLabel())
            ->badge($badge)
            ->badgeColor($this->getBadgeColor($badge))
            ->badgeTooltip($this->getBadgeTooltip($badge))
            ->icon($this->getIcon())
            ->iconPosition($this->getIconPosition());

        return $this->evaluate($this->modifyTabUsing, ['tab' => $tab]) ?? $tab;
    }

    /**
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager
     *
     * @return class-string<RelationManager>
     */
    protected function normalizeRelationManagerClass(string|RelationManagerConfiguration $manager): string
    {
        return $manager instanceof RelationManagerConfiguration ? $manager->relationManager : $manager;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'ownerRecord' => ClosureParameter::make($this->getOwnerRecord()),
            'pageClass' => ClosureParameter::make($this->getPageClass()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): ?ClosureParameter
    {
        $ownerRecord = $this->getOwnerRecord();

        if ($ownerRecord === null || ! $ownerRecord instanceof $parameterType) {
            return parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType);
        }

        return ClosureParameter::make($ownerRecord);
    }
}
