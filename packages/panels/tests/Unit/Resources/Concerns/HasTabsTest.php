<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Concerns\HasTabs;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Components\Tabs\Tab;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

/*
 * The host is declared here rather than under `tests/Fixtures/`: it carries no
 * `#[AsLiveComponent]`, and the Kernel's PSR-4 glob would autoconfigure it as a
 * service of its own.
 */
final class TabsProbe
{
    use HasTabs;

    public int $tablePage = 1;

    public int $flushes = 0;

    /**
     * @var array<string, Tab>
     */
    public array $tabs = [];

    public ?UrlMapping $mapping = null;

    public function flushCachedTableRecords(): void
    {
        $this->flushes++;
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function probeQuery(QueryBuilder $query, bool $isResolvingRecord = false): QueryBuilder
    {
        return $this->modifyQueryWithActiveTab($query, $isResolvingRecord);
    }

    private function getTableUrlMapping(string $prop): ?UrlMapping
    {
        return $prop === 'tab' ? $this->mapping : null;
    }
}

function articleQuery(): QueryBuilder
{
    return EntityManagerFactory::create()
        ->createQueryBuilder()
        ->select('record')
        ->from(Article::class, 'record');
}

it('answers no tabs by default', function (): void {
    $probe = new TabsProbe();

    expect($probe->getTabs())->toBe([])
        ->and($probe->getCachedTabs())->toBe([])
        ->and($probe->getDefaultActiveTab())->toBeNull();
});

it('stamps a generated label on a tab that declares none', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = [
        'all' => Tab::make(),
        'in_review' => Tab::make(),
        'drafts' => Tab::make('Only drafts'),
    ];

    $tabs = $probe->getCachedTabs();

    expect($tabs['all']->getLabel())->toBe('All')
        ->and($tabs['in_review']->getLabel())->toBe('In review')
        ->and($tabs['drafts']->getLabel())->toBe('Only drafts');
});

it('memoises the cached tabs', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = ['all' => Tab::make()];

    $first = $probe->getCachedTabs();
    $probe->tabs = ['other' => Tab::make()];

    expect($probe->getCachedTabs())->toBe($first);
});

it('generates a label from a key of either separator', function (): void {
    $probe = new TabsProbe();

    expect($probe->generateTabLabel('in_review'))->toBe('In review')
        ->and($probe->generateTabLabel('needs-work'))->toBe('Needs work')
        ->and($probe->generateTabLabel('all'))->toBe('All');
});

it('defaults the active tab to the first registered key', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = ['drafts' => Tab::make(), 'all' => Tab::make()];

    $probe->loadDefaultActiveTab();

    expect($probe->getDefaultActiveTab())->toBe('drafts')
        ->and($probe->activeTab)->toBe('drafts');
});

it('leaves an already-filled active tab alone', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = ['drafts' => Tab::make(), 'all' => Tab::make()];
    $probe->activeTab = 'all';

    $probe->loadDefaultActiveTab();

    expect($probe->activeTab)->toBe('all');
});

it('resets the page and flushes the records when the tab changes', function (): void {
    $probe = new TabsProbe();
    $probe->tablePage = 4;

    $probe->updatedActiveTab();

    expect($probe->tablePage)->toBe(1)
        ->and($probe->flushes)->toBe(1);
});

it('narrows the query through the active tab', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = [
        'all' => Tab::make(),
        'drafts' => Tab::make()->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.status = 'draft'")),
    ];
    $probe->activeTab = 'drafts';

    expect($probe->probeQuery(articleQuery())->getDQL())->toContain("record.status = 'draft'");
});

it('leaves the query alone when no tab is active', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = [
        'drafts' => Tab::make()->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.status = 'draft'")),
    ];

    expect($probe->probeQuery(articleQuery())->getDQL())->not->toContain('record.status');
});

it('heals a stale tab key silently', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = [
        'drafts' => Tab::make()->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.status = 'draft'")),
    ];
    $probe->activeTab = 'nope';

    expect($probe->probeQuery(articleQuery())->getDQL())->not->toContain('record.status');
});

it('skips a tab excluding its query while a record is resolved', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = [
        'drafts' => Tab::make()
            ->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.status = 'draft'"))
            ->excludeQueryWhenResolvingRecord(),
    ];
    $probe->activeTab = 'drafts';

    expect($probe->probeQuery(articleQuery(), isResolvingRecord: true)->getDQL())->not->toContain('record.status')
        ->and($probe->probeQuery(articleQuery())->getDQL())->toContain('record.status');
});

it('applies a tab still scoping a resolved record', function (): void {
    $probe = new TabsProbe();
    $probe->tabs = [
        'drafts' => Tab::make()->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.status = 'draft'")),
    ];
    $probe->activeTab = 'drafts';

    expect($probe->probeQuery(articleQuery(), isResolvingRecord: true)->getDQL())->toContain('record.status');
});

it('binds the active tab prop only where the host maps it', function (): void {
    $probe = new TabsProbe();

    expect($probe->modifyActiveTabProp(new LiveProp())->url())->toBeFalse();

    $probe->mapping = new UrlMapping(as: 'tab');

    expect($probe->modifyActiveTabProp(new LiveProp())->url())->toBe($probe->mapping);
});
