<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\EmbeddedSchemaHost;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('renders the schema of its host it is named by where it sits, and no schema it is not named by', function (): void {
    $page = $this->createLiveComponent(EmbeddedSchemaHost::class)->render()->crawler();

    expect($page)->toContainElement(Selectors::schemaKeyed('page'), 'Before Notes Title After Archive')
        ->toContainElement('label', 'Notes')
        ->not->toContainElement('label', 'Reason');
});

it('repeats the ids of a schema its host also renders elsewhere, and not those of one it renders nowhere else', function (): void {
    $page = $this->createLiveComponent(EmbeddedSchemaHost::class)->render()->crawler();

    expect($page->filter('input[id="form.title"]'))->toHaveCount(2)
        ->and($page->filter('input[id="details.notes"]'))->toHaveCount(1);
});
