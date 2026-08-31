<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;

it('collects categories in insertion order and overwrites a repeated name', function (): void {
    $a = new GlobalSearchResult('A', '/a');
    $b = new GlobalSearchResult('B', '/b');

    $results = GlobalSearchResults::make()
        ->category('articles', [$a])
        ->category('topics', [$b])
        ->category('articles', [$b]);

    expect($results->getCategories())->toBe(['articles' => [$b], 'topics' => [$b]]);
});

it('accepts a traversable result set', function (): void {
    $a = new GlobalSearchResult('A', '/a');

    $results = GlobalSearchResults::make()->category('articles', new ArrayIterator([$a]));

    expect($results->getCategories())->toBe(['articles' => [$a]]);
});

it('starts empty', function (): void {
    expect(GlobalSearchResults::make()->getCategories())->toBe([]);
});
