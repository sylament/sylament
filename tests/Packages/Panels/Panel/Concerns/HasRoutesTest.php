<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("serves the panel's pages under its path", function (): void {
    expect($this->visit('/panel-routing/settings')->filter(Selectors::PAGE_HEADING)->text())->toBe('Slugged Settings Page');
});
