<?php

declare(strict_types=1);

use Doctrine\ORM\Query\Expr\Orx;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Tables\SeedlingGardenTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

it('renders its own search input for an individually searchable column alone', function (): void {
    $this->seed(new Seedling('Oak'));

    $crawler = $this->createLiveComponent(SeedlingGardenTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::SEARCH))->toHaveCount(1)
        ->and($crawler->filter(Selectors::columnSearch('name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::columnSearch('garden.name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('id')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::columnSearch('id')))->toHaveCount(0);
});

it('leaves a column searchable on its own input alone out of the global search', function (): void {
    $kew = new Garden('Kew');
    $oak = new Seedling('Oak');
    $oak->garden = $kew;
    $this->seed($kew, $oak, $kewpie = new Seedling('Kewpie'));

    $this->createLiveComponent(SeedlingGardenTable::class)
        ->searchTable('Kew')
        ->assertCanSeeTableRecords([$kewpie])
        ->assertCanNotSeeTableRecords([$oak])
        ->searchTable()
        ->searchTableColumns(['garden' => ['name' => 'Kew']])
        ->assertCanSeeTableRecords([$oak])
        ->assertCanNotSeeTableRecords([$kewpie]);
});

it("splits a column's own term into words as the column says, while the global term follows the table", function (): void {
    $this->seed($redOak = new Seedling('Red oak'), $oakRed = new Seedling('Oak, red'), $elm = new Seedling('Elm'));

    $this->createLiveComponent(SeedlingGardenTable::class)
        ->searchTableColumns(['name' => 'Red oak'])
        ->assertCanSeeTableRecords([$redOak])
        ->assertCanNotSeeTableRecords([$oakRed, $elm])
        ->searchTableColumns([])
        ->searchTable('Red oak')
        ->assertCanSeeTableRecords([$redOak, $oakRed])
        ->assertCanNotSeeTableRecords([$elm]);
});

it('folds case as forceSearchCaseInsensitive() says, and as its platform does when it says nothing', function (): void {
    $like = function (TestPlatform $platform, ?bool $forced): string {
        $query = $this->platformEntityManager($platform)->createQueryBuilder()->select('product')->from(Product::class, 'product');
        $group = new Orx();
        $column = TextColumn::make('name')->searchable();

        if ($forced !== null) {
            $column->forceSearchCaseInsensitive($forced);
        }

        $column->applySearchConstraint($query, $group, 'Lamp');

        return (string) $group;
    };

    expect($like(TestPlatform::PostgreSql, null))->toBe('LOWER(product.name) LIKE :sylament_search_0')
        ->and($like(TestPlatform::MySql, null))->toBe('product.name LIKE :sylament_search_0')
        ->and($like(TestPlatform::MySql, true))->toBe('LOWER(product.name) LIKE :sylament_search_0')
        ->and($like(TestPlatform::PostgreSql, false))->toBe('product.name LIKE :sylament_search_0');
});
