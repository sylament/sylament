<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;

it('draws its icon after its label when positioned after, and before it otherwise', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->icon('support:star'),
        Tab::make('Closed')->icon('support:star')->iconPosition(IconPosition::After),
    ])))->toContainButtons(SchemasSelectors::TAB, ['StarOpen', 'ClosedStar']);
});
