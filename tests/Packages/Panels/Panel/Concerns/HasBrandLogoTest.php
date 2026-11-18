<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("hands the shell the brand's logo, its dark-mode variant and their height", function (): void {
    $page = $this->visit('/panel-branded');

    expect($page->filter(Selectors::LOGO . '.sy-pa-logo-light')->attr('src'))->toBe('/brand/acme.svg')
        ->and($page->filter(Selectors::LOGO . '.sy-pa-logo-dark')->attr('src'))->toBe('/brand/acme-dark.svg')
        ->and(array_unique($page->filter(Selectors::LOGO)->each(fn ($logo): ?string => $logo->attr('style'))))->toBe(['height: 2rem']);
});
