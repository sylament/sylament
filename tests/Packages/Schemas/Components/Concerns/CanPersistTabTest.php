<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('has the browser remember the open tab under the id of persisted tabs, and of no others', function (): void {
    $forgotten = Selectors::TABS . ':not([data-sylament--schemas--tabs-persist-value]):not([data-sylament--schemas--tabs-persist-id-value])';

    expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTab()->tabs([Tab::make('Open')])))
        ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-persist-value="true"][data-sylament--schemas--tabs-persist-id-value="schema.orders::data::tabs"]', 'Open')
        ->and($this->renderSchemaComponent(Tabs::make()->persistTab()->tabs([Tab::make('Open')])))
        ->toContainElement($forgotten, 'Open')
        ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')])))
        ->toContainElement($forgotten, 'Open');
});
