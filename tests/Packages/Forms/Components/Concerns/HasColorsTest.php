<?php

declare(strict_types=1);

use Sylament\Forms\Components\ToggleButtons;
use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Forms\ColoredLevel;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

describe('colors()', function (): void {
    it('paints each option in its colour, by name or palette, and an unmapped one primary', function (bool $grouped): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High', 'mid' => 'Mid'])->colors(['low' => 'success', 'high' => Color::Rose])->grouped($grouped)))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::COLORED . SupportSelectors::color('success'), 'Low')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]), 'High')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::COLORED . SupportSelectors::color('primary'), 'Mid');
    })->with([
        'the default tree' => [false],
        'the grouped tree' => [true],
    ]);
});

describe('enum fallback', function (): void {
    it('takes its colours from a HasColor enum only while colors() is blank', function (): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->enum(ColoredLevel::class)))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::color('success'), 'Heart Low')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::color('danger'), 'Star High')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::color('primary'), 'Unrated')
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->enum(ColoredLevel::class)->colors(['high' => 'info'])))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::color('info'), 'Star High')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . SupportSelectors::color('primary'), 'Heart Low');
    });
});
