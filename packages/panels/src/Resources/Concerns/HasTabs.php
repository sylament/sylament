<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Concerns;

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\TwigComponent\Attribute\PostMount;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Symfony\Component\String\u;

/**
 * The record filters a listing offers as a strip above its table: the tabs a
 * host declares, the one that is active, and the scope that one lays over the
 * table's query.
 */
trait HasTabs
{
    /**
     * Writable because the strip drives it through `live#update`, and bound to
     * the query string only where the host maps `tab` — a relation manager
     * carries tabs with no URL of its own.
     */
    #[LiveProp(writable: true, onUpdated: 'updatedActiveTab', modifier: 'modifyActiveTabProp')]
    public ?string $activeTab = null;

    /**
     * @var array<string, Tab>|null
     */
    protected ?array $cachedTabs = null;

    public function modifyActiveTabProp(LiveProp $liveProp): LiveProp
    {
        $mapping = $this->getTableUrlMapping('tab');

        return $mapping instanceof UrlMapping ? $liveProp->withUrl($mapping) : $liveProp;
    }

    /**
     * The user hook: the tabs this listing offers, keyed by the value the
     * active-tab property carries.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getCachedTabs(): array
    {
        if ($this->cachedTabs !== null) {
            return $this->cachedTabs;
        }

        $tabs = $this->getTabs();

        foreach ($tabs as $key => $tab) {
            if (!$tab->hasCustomLabel()) {
                $tab->label($this->generateTabLabel($key));
            }
        }

        return $this->cachedTabs = $tabs;
    }

    public function getDefaultActiveTab(): ?string
    {
        return array_key_first($this->getCachedTabs());
    }

    /**
     * The mount hook, and the one seam that builds — and memoises — the tabs on
     * a first render. It runs at post-mount rather than from `mount()` because
     * only there is every property in place: {@see \Symfony\UX\TwigComponent\ComponentFactory::mountFromObject()}
     * assigns whatever `mount()` did not consume *after* it has run, so a
     * `getTabs()` reading any other property would otherwise build against the
     * declared default. Above the table's own post-mount hooks, so anything
     * they reach reads a resolved tab.
     *
     * It also runs after UX's query-string seeding, which is a listener on the
     * same event ({@see \Symfony\UX\LiveComponent\EventListener\RequestInitializeSubscriber}
     * at priority 256, and every listener runs before the first `#[PostMount]`
     * method) — so a `?tab=` naming a tab still wins here, while a blank one and
     * one naming a tab the host no longer declares are both healed before the
     * props are dehydrated. Without the second arm a bookmarked key renders a
     * strip with nothing selected over rows nothing scoped.
     */
    #[PostMount(priority: 30)]
    public function loadDefaultActiveTab(): void
    {
        if (filled($this->activeTab) && array_key_exists($this->activeTab, $this->getCachedTabs())) {
            return;
        }

        $this->activeTab = $this->getDefaultActiveTab();
    }

    public function generateTabLabel(string $key): string
    {
        return u($key)->replace('_', ' ')->replace('-', ' ')->title()->toString();
    }

    /**
     * The strip is the only element that writes this property, so a write is
     * the untrusted edge: a blanked key is healed here, where the healed value
     * still reaches the wire, rather than only at the render that would
     * otherwise carry a null `data-value` off the page.
     */
    public function updatedActiveTab(): void
    {
        $this->loadDefaultActiveTab();

        $this->tablePage = 1;

        $this->flushCachedTableRecords();
    }

    /**
     * The tab strip, in the server-driven mode that writes the active key back
     * onto this host — built at render time, so it costs nothing on a host that
     * declares no tabs.
     *
     * The default is reloaded here as well as at the mount and the update: a host
     * with tabs must never render the strip with a null active tab.
     */
    public function getTabsSchema(): Schema
    {
        $this->loadDefaultActiveTab();

        return $this->makeSchema()
            ->key('tabs')
            ->components([
                Tabs::make()
                    ->key('resourceTabs')
                    ->liveComponentProperty('activeTab')
                    ->contained(false)
                    ->tabs($this->getCachedTabs()),
            ]);
    }

    /**
     * A blank tab and a key the host no longer declares are both no-ops — the
     * mount hook and the strip's own update heal the second one before it
     * reaches here, so this is the fence for a query that runs ahead of either.
     */
    protected function modifyQueryWithActiveTab(QueryBuilder $query, bool $isResolvingRecord = false): QueryBuilder
    {
        if (blank($this->activeTab)) {
            return $query;
        }

        $tabs = $this->getCachedTabs();

        if (!array_key_exists($this->activeTab, $tabs)) {
            return $query;
        }

        $tab = $tabs[$this->activeTab];

        if ($isResolvingRecord && $tab->shouldExcludeQueryWhenResolvingRecord()) {
            return $query;
        }

        return $tab->modifyQuery($query);
    }
}
