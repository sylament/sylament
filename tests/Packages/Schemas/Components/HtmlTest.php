<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('prints its content into the schema as markup, and nothing without content', function (): void {
    expect($this->renderSchemaComponent(Html::make('<p>Ships <b>today</b></p>')))
        ->toContainElement(Selectors::COMPONENT_CELL . ' p', 'Ships today')
        ->toContainElement(Selectors::COMPONENT_CELL . ' p b', 'today')
        ->and($this->renderSchemaComponent(Html::make(null)))
        ->toContainElement(Selectors::COMPONENT_CELL, '');
});
