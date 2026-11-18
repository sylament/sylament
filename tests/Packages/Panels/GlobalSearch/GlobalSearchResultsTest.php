<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;

it('keeps each category under its name in the order given, a generator of results read into a list', function (): void {
    $moon = new GlobalSearchResult(title: 'Moon', url: '/moon');
    $sun = new GlobalSearchResult(title: 'Sun', url: '/sun');
    $generated = (function () use ($sun): Generator {
        yield $sun;
    })();

    expect(GlobalSearchResults::make()->getCategories())->toBe([])
        ->and(GlobalSearchResults::make()->category('Stars', $generated)->category('Moons', [$moon])->getCategories())->toBe(['Stars' => [$sun], 'Moons' => [$moon]]);
});
