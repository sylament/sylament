<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('lays its children out in a row without a grid, and lets a child that grows take the room left', function (): void {
    $render = $this->renderViewComponent(Schema::make()->inline()->columns(3)->components([
        Text::make('Fixed'),
        Text::make('Growing')->grow(),
    ]));

    expect($render)
        ->toContainElement(Selectors::SCHEMA . SupportSelectors::INLINE . ':not([style])')
        ->toContainElement(Selectors::GROWABLE . ' ' . Selectors::TEXT, 'Growing')
        ->toContainElement(Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Fixed')
        ->and($render->filter(Selectors::GROWABLE))->toHaveCount(1)
        ->and($render->filter(SupportSelectors::ANY_COLUMN_SPAN))->toHaveCount(0);
});
