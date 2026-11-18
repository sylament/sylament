<?php

declare(strict_types=1);

use Sylament\Forms\Components\Toggle;
use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('paints the control root in the off colour and the track in the on colour', function (): void {
    expect($this->renderSchemaComponent(Toggle::make('notify')->offColor('danger')->onColor('success')))
        ->toContainElement(Selectors::TOGGLE . SupportSelectors::COLORED . SupportSelectors::color('danger') . ':not(' . SupportSelectors::color('success') . ')')
        ->toContainElement(Selectors::TOGGLE_TRACK . SupportSelectors::COLORED . SupportSelectors::color('success') . ':not(' . SupportSelectors::color('danger') . ')');
});

it('falls back to a gray switch with a primary track, and paints a gray track when told', function (): void {
    expect($this->renderSchemaComponent(Toggle::make('notify')))
        ->toContainElement(Selectors::TOGGLE . SupportSelectors::GRAY)
        ->toContainElement(Selectors::TOGGLE_TRACK . SupportSelectors::COLORED . SupportSelectors::color('primary'))
        ->and($this->renderSchemaComponent(Toggle::make('notify')->onColor('gray')))
        ->toContainElement(Selectors::TOGGLE_TRACK . SupportSelectors::GRAY);
});

it('marks a track whose colour name is unknown without painting it any shade', function (): void {
    expect($this->renderSchemaComponent(Toggle::make('notify')->offColor('danger')->onColor('nonsense')))
        ->toContainElement(Selectors::TOGGLE_TRACK . SupportSelectors::COLORED . ':not(' . SupportSelectors::BACKGROUND_COLORED . '):not(' . SupportSelectors::color('danger') . ')');
});

it('renders an array palette as inline colour declarations on the element it colours', function (): void {
    expect($this->renderSchemaComponent(Toggle::make('notify')->offColor(Color::Zinc)->onColor(Color::Rose)))
        ->toContainElement(Selectors::TOGGLE . SupportSelectors::COLORED . SupportSelectors::palette(Color::Zinc[500]) . ':not(' . SupportSelectors::palette(Color::Rose[500]) . ')')
        ->toContainElement(Selectors::TOGGLE_TRACK . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]));
});
