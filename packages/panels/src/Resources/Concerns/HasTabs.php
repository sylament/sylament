<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Concerns;

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

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
     * Called from `mount()`, which runs before the query string is seeded — so
     * a `?tab=` in the URL still wins over the default this leaves behind.
     */
    public function loadDefaultActiveTab(): void
    {
        if (filled($this->activeTab)) {
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
     * A null active key renders `data-value` as null and support's `TabsItem`
     * refuses a null attribute, so the page 500s. The default is reloaded here
     * as well as at the mount and the update, because the URL seeding runs
     * *after* the mount and an empty `?tab=` overwrites what it left behind.
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
     * second is what heals a bookmarked `?tab=` naming a tab that has since
     * gone.
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
