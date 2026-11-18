<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('stripes every other row of a striped table and no row of a plain one', function (): void {
    $this->seed(new LarderJar('Apricot'), new LarderJar('Bramble'), new LarderJar('Cherry'), new Product('Lamp'), new Product('Desk'));

    $striped = $this->createLiveComponent(HeadedLarderJarTable::class)->render()->crawler();
    $plain = $this->createLiveComponent(ProductTable::class)->render()->crawler();

    expect($striped->filter(Selectors::ROW)->each(static fn (Crawler $row): bool => $row->matches('.sy-striped')))->toBe([false, true, false])
        ->and($plain->filter(Selectors::ROW))->toHaveCount(2)
        ->and($plain->filter(Selectors::ROW . '.sy-striped'))->toHaveCount(0);
});
