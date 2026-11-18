<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Tables\SeedlingGardenTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders a sort trigger on a sortable column, one declaring its sort columns included, and none on a column never made sortable', function (): void {
    $this->seed(new Seedling('Oak'));

    $crawler = $this->createLiveComponent(SeedlingGardenTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::sortTrigger('name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::sortTrigger('garden.name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('id')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::sortTrigger('id')))->toHaveCount(0);
});
