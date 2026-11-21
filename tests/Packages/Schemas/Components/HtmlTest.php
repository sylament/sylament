<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Tests\Fixtures\Schemas\Selectors;

use function Symfony\Component\Translation\t;

it('prints its content into the schema as markup, and nothing without content', function (): void {
    expect($this->renderSchemaComponent(Html::make('<p>Ships <b>today</b></p>')))
        ->toContainElement(Selectors::COMPONENT_CELL . ' p', 'Ships today')
        ->toContainElement(Selectors::COMPONENT_CELL . ' p b', 'today')
        ->and($this->renderSchemaComponent(Html::make(null)))
        ->toContainElement(Selectors::COMPONENT_CELL, '');
});

it('prints a message it is given as the markup it translates to, so a parameter carrying markup is not escaped for the application', function (): void {
    expect($this->renderSchemaComponent(Html::make(t('raw', ['x' => '<b>x</b>'], 'fixtures-schemas'))))
        ->toContainElement(Selectors::COMPONENT_CELL . ' b', 'x');
});
