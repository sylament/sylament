<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('lays its children out in a row without a grid, and lets a child that grows take the room left', function (): void {
    $render = $this->renderViewComponent(Schema::make()->inline()->columns(3)->components([
        Text::make('Fixed'),
        Text::make('Growing')->grow(),
    ]));

    expect($render)
        ->toContainElement(Selectors::SCHEMA . Selectors::INLINE . ':not([style])')
        ->toContainElement('.sy-growable ' . Selectors::TEXT, 'Growing')
        ->toContainElement(Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Fixed')
        ->and($render->filter('.sy-growable'))->toHaveCount(1)
        ->and($render->filter('[style*="--col-span"]'))->toHaveCount(0);
});
