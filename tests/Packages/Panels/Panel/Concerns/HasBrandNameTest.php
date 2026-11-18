<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("titles the document with the page's title and then the brand name, and names the brand's logo by it", function (): void {
    $page = $this->visit('/panel-branded');

    expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Overview — Acme')
        ->and(array_unique($page->filter(Selectors::LOGO)->each(fn ($logo): ?string => $logo->attr('alt'))))->toBe(['Acme logo']);
});

it('titles the document with the page title alone on a panel with no brand name', function (): void {
    expect($this->visit('/panel-routing')->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Dashboard');
});
