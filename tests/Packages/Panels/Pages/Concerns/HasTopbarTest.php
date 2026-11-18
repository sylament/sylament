<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it('shows the top strip, with its skip link and user menu, above a simple page by default, and none above one that turns it off', function (): void {
    $card = $this->visit('/simple-knobs/card-simple-page');
    $bare = $this->visit('/simple-knobs/bare-simple-page');

    expect($card->filter(Selectors::SKIP_LINK))->toHaveCount(1)
        ->and($card->filter(Selectors::USER_MENU_TRIGGER))->toHaveCount(1)
        ->and($bare->filter(Selectors::SIMPLE_LAYOUT . ' ' . Selectors::SIMPLE_PAGE))->toHaveCount(1)
        ->and($bare->filter(Selectors::SKIP_LINK))->toHaveCount(0)
        ->and($bare->filter(Selectors::USER_MENU_TRIGGER))->toHaveCount(0);
});
