<?php

declare(strict_types=1);

use Sylament\Forms\Components\ToggleButtons;
use Sylament\Tests\Fixtures\Forms\ColoredLevel;
use Sylament\Tests\Fixtures\Forms\Selectors;

describe('icons()', function (): void {
    it("draws each option's icon beside its label, and none for an unmapped one", function (bool $grouped): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->icons(['low' => 'support:heart'])->grouped($grouped)))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION, 'Heart Low')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . ':not(:has(svg))', 'High');
    })->with([
        'the default tree' => [false],
        'the grouped tree' => [true],
    ]);
});

describe('enum fallback', function (): void {
    it('takes its icons from a HasIcon enum only while icons() is blank', function (): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->enum(ColoredLevel::class)))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION, 'Heart Low')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION, 'Star High')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . ':not(:has(svg))', 'Unrated')
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->enum(ColoredLevel::class)->icons(['high' => 'support:heart'])))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION, 'Heart High')
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . ':not(:has(svg))', 'Low');
    });
});
