<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages\Concerns;

use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Resources\RelationManagers\RelationManagerConfiguration;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\LiveComponent;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The page half of relation managers: the registrations a resource declares,
 * filtered to the ones this record shows, and the one component that mounts
 * them — a tab bar, a bare embed, a group's embeds, or nothing at all.
 */
trait HasRelationManagers
{
    #[LiveProp(writable: true)]
    public ?string $activeRelationManager = null;

    /**
     * @var array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>|null
     */
    protected ?array $cachedRelationManagers = null;

    /**
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    public function getCachedRelationManagers(): array
    {
        if (! $this->hasRecord()) {
            return [];
        }

        return $this->cachedRelationManagers ??= $this->getRelationManagers();
    }

    /**
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    public function getRelationManagers(): array
    {
        return array_filter(
            $this->getAllRelationManagers(),
            function (string|RelationGroup|RelationManagerConfiguration $manager): bool {
                if ($manager instanceof RelationGroup) {
                    return $manager->ownerRecord($this->getRecord())->pageClass(static::class)->getManagers() !== [];
                }

                return $this->normalizeRelationManagerClass($manager)::canViewForRecord($this->getRecord(), static::class);
            },
        );
    }

    /**
     * The single component the content schema holds. Filament's four arms minus
     * the combined content tab: many managers behind a tab bar, one group as a
     * plain container of its embeds, one manager as a bare embed, and none at
     * all as a hidden group — so the content schema is never empty and the page
     * template needs no branch of its own.
     */
    public function getRelationManagersContentComponent(): Component
    {
        $managers = $this->getCachedRelationManagers();

        $this->healActiveRelationManager($managers);

        if (count($managers) > 1) {
            $tabs = [];

            // The registration keys are carried through: `Tabs` re-keys its tabs
            // from them and compares the active manager against them, so
            // re-indexing here would break switching with no error anywhere.
            foreach ($managers as $tabKey => $manager) {
                $tabs[$tabKey] = $manager instanceof RelationGroup
                    ? $this->makeRelationGroupTab($manager)
                    : $this->makeRelationManagerTab($manager);
            }

            return Tabs::make()
                ->key('relationManagerTabs')
                ->liveComponentProperty('activeRelationManager')
                ->contained(false)
                ->tabs($tabs);
        }

        $firstKey = array_key_first($managers);

        if ($firstKey === null) {
            return Group::make()->hidden();
        }

        $manager = $managers[$firstKey];

        if ($manager instanceof RelationGroup) {
            $manager->ownerRecord($this->getRecord())->pageClass(static::class);

            return Group::make($this->makeRelationGroupEmbeds($manager));
        }

        return $this->makeRelationManagerEmbed($manager, $this->normalizeRelationManagerClass($manager));
    }

    /**
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    protected function getAllRelationManagers(): array
    {
        return $this->getResourceInstance()->getRelations();
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

    /**
     * Filament runs this from a `rendering` hook, which UX has no counterpart
     * for; folding it into the builder keeps the self-healing — an active
     * manager the registrations no longer name falls back to the first on every
     * render — and the string cast is what keeps an integer registration key
     * out of a `?string` property.
     *
     * @param array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration> $managers
     */
    private function healActiveRelationManager(array $managers): void
    {
        if (array_key_exists($this->activeRelationManager ?? '', $managers)) {
            return;
        }

        $firstKey = array_key_first($managers);

        $this->activeRelationManager = $firstKey === null ? null : (string) $firstKey;
    }

    /**
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager
     */
    private function makeRelationManagerTab(string|RelationManagerConfiguration $manager): Tab
    {
        $class = $this->normalizeRelationManagerClass($manager);

        return $class::getTabComponent($this->getRecord(), static::class)
            ->components(fn (): array => [$this->makeRelationManagerEmbed($manager, $class)]);
    }

    private function makeRelationGroupTab(RelationGroup $group): Tab
    {
        $group->ownerRecord($this->getRecord())->pageClass(static::class);

        return $group->getTabComponent()
            ->components(fn (): array => $this->makeRelationGroupEmbeds($group));
    }

    /**
     * The group arrives with its owner and page already on it — both arms set
     * them before they ask it for anything, exactly as Filament's do.
     *
     * @return list<LiveComponent>
     */
    private function makeRelationGroupEmbeds(RelationGroup $group): array
    {
        $embeds = [];

        foreach ($group->getManagers() as $groupKey => $manager) {
            $class = $this->normalizeRelationManagerClass($manager);

            // The group key joins the class, so one manager registered in two
            // groups still keeps two identities across renders.
            $embeds[] = $this->makeRelationManagerEmbed($manager, $class, $class . '-' . $groupKey);
        }

        return $embeds;
    }

    /**
     * The owner rides the embed as its route key rather than the record itself,
     * because that is what the manager's own wire property holds.
     *
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager
     * @param class-string<RelationManager> $class
     */
    private function makeRelationManagerEmbed(string|RelationManagerConfiguration $manager, string $class, ?string $key = null): LiveComponent
    {
        return LiveComponent::make($class, [
            'ownerRecord' => $this->getRecordKey(),
            'pageClass' => static::class,
            ...$class::getDefaultProperties(),
            ...($manager instanceof RelationManagerConfiguration ? $manager->getProperties() : []),
        ])->key($key ?? $class);
    }
}
