<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;

it('stacks its children until the breakpoint it is given, and lays them out side by side from the smallest screen without one', function (): void {
    expect($this->renderSchemaComponent(Flex::make([Text::make('Total')])->from('md')))
        ->toContainElement(SchemasSelectors::FLEX . '.sy-from-md:not(.sy-from-default)', 'Total')
        ->and($this->renderSchemaComponent(Flex::make([Text::make('Total')])))
        ->toContainElement(SchemasSelectors::FLEX . '.sy-from-default', 'Total');
});
