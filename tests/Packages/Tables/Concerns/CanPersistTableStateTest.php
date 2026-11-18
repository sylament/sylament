<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\BrowsableProductTable;
use Sylament\Tests\Fixtures\Tables\RememberingProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TablePageController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it("remembers the sort, search, column searches, filters and page size a client chose across that client's next page load", function (): void {
    $this->seed(new Product('Clamp'), new Product('Lamp'), new Product('Lampshade'), new Product('Lamb'), new Product('Ramp'), new Product('Desk'));
    $client = $this::getContainer()->get('test.client');
    $names = static fn (Crawler $page): array => $page->filter(Selectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text());

    $this->createLiveComponent(RememberingProductTable::class, client: $client)
        ->sortTable('name', 'desc')
        ->searchTable('amp')
        ->searchTableColumns(['name' => 'lam'])
        ->filterTable('short')
        ->set('tableRecordsPerPage', '5');

    $remembered = $this->visit(TablePageController::url('Tables:RememberingProductTable'), client: $client);
    $fresh = $this->visit(TablePageController::url('Tables:RememberingProductTable'));

    expect($names($remembered))->toBe(['Lamp', 'Clamp'])
        ->and($remembered->filter(Selectors::columnSearch('name'))->attr('value'))->toBe('lam')
        ->and($remembered->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['Search: amp', 'Name: lam', 'Short'])
        ->and($remembered->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->text())->toBe('5')
        ->and($names($fresh))->toBe(['Clamp', 'Lamp', 'Lampshade', 'Lamb', 'Ramp', 'Desk'])
        ->and($fresh->filter(Selectors::columnSearch('name'))->attr('value'))->toBe('')
        ->and($fresh->filter(Selectors::FILTER_INDICATOR))->toHaveCount(0)
        ->and($fresh->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->text())->toBe('10');
});

it('remembers only the page size of a table that opted into remembering nothing', function (): void {
    $this->seed(new Product('Lamp'), new Product('Desk'));
    $client = $this::getContainer()->get('test.client');

    $this->createLiveComponent(BrowsableProductTable::class, client: $client)
        ->sortTable('name', 'desc')
        ->searchTable('La')
        ->set('tableRecordsPerPage', '5');

    $page = $this->visit(TablePageController::url('Tables:BrowsableProductTable'), client: $client);

    expect($page->filter(Selectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Desk', 'Lamp'])
        ->and($page->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->text())->toBe('5');
});

it("shares one remembered state between every instance of one host and none with another host's", function (): void {
    $this->seed(new Product('Lamp'), new Product('Desk'));
    $client = $this::getContainer()->get('test.client');

    $this->createLiveComponent(RememberingProductTable::class, client: $client)->set('tableRecordsPerPage', '5');

    $same = $this->visit(TablePageController::url('Tables:RememberingProductTable'), client: $client);
    $other = $this->visit(TablePageController::url('Tables:ProductTable'), client: $client);

    expect($same->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->text())->toBe('5')
        ->and($other->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->text())->toBe('10');
});

it('lets the URL win over what the table remembers, and fills from it only what the URL left blank', function (): void {
    $this->seed(new Product('Clamp'), new Product('Lamp'), new Product('Lampshade'), new Product('Lamb'), new Product('Ramp'), new Product('Desk'));
    $client = $this::getContainer()->get('test.client');

    $this->createLiveComponent(RememberingProductTable::class, client: $client)
        ->sortTable('name', 'desc')
        ->searchTable('amp');

    $page = $this->visit(TablePageController::url('Tables:RememberingProductTable') . '?search=lam', client: $client);

    expect($page->filter(Selectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Lampshade', 'Lamp', 'Lamb', 'Clamp']);
});

it('forgets a remembered sort once a URL names a sort the table does not offer', function (): void {
    $this->seed(new Product('Desk'), new Product('Lamp'));
    $client = $this::getContainer()->get('test.client');
    $url = TablePageController::url('Tables:RememberingProductTable');

    $this->createLiveComponent(RememberingProductTable::class, client: $client)->sortTable('name', 'desc');

    $remembered = $this->visit($url, client: $client)->filter(Selectors::columnCells('name'))->each(static fn (Crawler $cell): string => $cell->text());
    $this->visit($url . '?sort=price:asc', client: $client);
    $forgotten = $this->visit($url, client: $client);

    expect($remembered)->toBe(['Lamp', 'Desk'])
        ->and($forgotten->filter(Selectors::sortState('name', 'none')))->toHaveCount(1);
});

it('forgets a remembered page size the table does not offer, and remembers none until one is chosen', function (): void {
    $this->seed(new Product('Lamp'));
    $key = 'sylament.tables.' . md5(RememberingProductTable::class) . '_per_page';
    $url = TablePageController::url('Tables:RememberingProductTable');
    $chooser = $this::getContainer()->get('test.client');
    $visitor = $this::getContainer()->get('test.client');

    $this->createLiveComponent(RememberingProductTable::class, client: $chooser)->set('tableRecordsPerPage', '25');
    $chosen = $chooser->getRequest()->getSession()->get($key);
    $page = $this->visit($url, client: $chooser);
    $this->visit($url, client: $visitor);

    expect($chosen)->toBe('25')
        ->and($page->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->text())->toBe('10')
        ->and($chooser->getRequest()->getSession()->has($key))->toBeFalse()
        ->and($visitor->getRequest()->getSession()->has($key))->toBeFalse();
});

it('remembers nothing and throws nothing without a session to remember in', function (bool $withRequest): void {
    $requestStack = new RequestStack();

    if ($withRequest) {
        $requestStack->push(new Request());
    }

    $sorting = new RememberingProductTable($this->entityManager());
    $sorting->setTableStateRequestStack($requestStack);
    $sorting->sortTable('name', 'desc');

    $mounted = new RememberingProductTable($this->entityManager());
    $mounted->setTableStateRequestStack($requestStack);

    expect($mounted->mountTablePersistedState())->toBeNull()
        ->and($sorting->getTableSortDirection())->toBe('desc')
        ->and($mounted->getTableSortColumn())->toBeNull();
})->with([
    'no request' => [false],
    'a request without a session' => [true],
]);
