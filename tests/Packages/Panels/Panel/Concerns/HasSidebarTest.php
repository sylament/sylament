<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it('lets every navigation group collapse unless the panel turns collapsible groups off', function (): void {
    $collapsible = $this->visit('/panel-routing');
    $flat = $this->visit('/panel-branded');

    expect($collapsible->filter(Selectors::navigationGroup('Help') . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE)->count())->toBe(1)
        ->and($flat->filter(Selectors::navigationGroup('Reports'))->count())->toBe(1)
        ->and($flat->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE)->count())->toBe(0);
});
