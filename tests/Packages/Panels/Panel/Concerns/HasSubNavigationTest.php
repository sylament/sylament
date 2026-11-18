<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("draws a page's band where its panel names, and at the start in a panel that names nowhere", function (): void {
    expect($this->visit('/navigation-band/banded')->filter(Selectors::pageWithSubNavigation('end')))->toHaveCount(1)
        ->and($this->visit('/navigation-start/banded')->filter(Selectors::pageWithSubNavigation('start')))->toHaveCount(1);
});
