<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\EmbeddedSchema;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\EmbeddedSchemaHostFixture;

use function Sylament\Schemas\Tests\renderToHtml;

it('renders the host schema the name points at', function (): void {
    $host = new EmbeddedSchemaHostFixture();
    $embed = EmbeddedSchema::make('form');
    Schema::make($host)->components([$embed])->getComponents();

    expect(renderToHtml($embed))->toContain('embedded form content');
});

it('renders the same schema instance the host already cached', function (): void {
    $host = new EmbeddedSchemaHostFixture();
    $embed = EmbeddedSchema::make('form');
    Schema::make($host)->components([$embed])->getComponents();

    renderToHtml($embed);

    expect($host->hasCachedSchema('form'))->toBeTrue();
});
