<?php

declare(strict_types=1);

use Sylament\Forms\Components\ToggleButtons;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Twig\Markup;

it("renders a tooltip as the option's native title, a Markup one as its literal markup, and none for an option without one", function (bool $grouped): void {
    expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High', 'mid' => 'Mid'])->tooltips(['low' => 'Cheap', 'high' => new Markup('<b>Dear</b>', 'UTF-8')])->grouped($grouped)))
        ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . '[title="Cheap"]', 'Low')
        ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . '[title="<b>Dear</b>"]', 'High')
        ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . ':not([title])', 'Mid');
})->with([
    'the default tree' => [false],
    'the grouped tree' => [true],
]);
