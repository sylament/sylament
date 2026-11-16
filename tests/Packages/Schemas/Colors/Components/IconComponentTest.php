<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Icon;
use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('paints an icon in a named colour or a palette, leaving the shade to its stylesheet', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->color('danger')))
        ->toContainElement(Selectors::ICON . SupportSelectors::color('danger') . ':not([class*="sy-text-color-"])', 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')->color(Color::Rose)))
        ->toContainElement(Selectors::ICON . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]) . ':not([style*="--sy-text"])', 'Star');
});

it('leaves an uncoloured icon and a gray one to the gray its stylesheet paints', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')))
        ->toContainElement(Selectors::ICON . SupportSelectors::GRAY, 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')->color('gray')))
        ->toContainElement(Selectors::ICON . SupportSelectors::GRAY, 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')->color('info')))
        ->toContainElement(Selectors::ICON . SupportSelectors::color('info'), 'Star');
});
