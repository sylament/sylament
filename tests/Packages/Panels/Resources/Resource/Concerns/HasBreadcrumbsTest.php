<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it("crumbs a resource's listing by the breadcrumb it declares, else by its plural record label in title case", function (): void {
    $pantry = new Larder('Pantry');
    $pantry->addBin($bin = new LarderBin('B1'));

    $bin->addNote($note = new LarderBinNote('Use first'));
    $this->seed($pantry);

    $crumbs = $this->visit("/nested/stocked-larders/{$pantry->id}/larder-bins/B1/larder-bin-notes/{$note->id}/edit")
        ->filter(SupportSelectors::BREADCRUMB)
        ->each(static fn (Crawler $crumb): string => $crumb->text());

    expect($crumbs)->toContain('Larder Bins', 'Notes')
        ->not->toContain('Larder Bin Notes');
});
