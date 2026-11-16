<?php

declare(strict_types=1);

use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('paints a text in a named colour through the shades it picks, and in a palette through its text variables', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->color('danger')))
        ->toContainElement(Selectors::TEXT . SupportSelectors::color('danger') . SupportSelectors::textShades(600, 300), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->color(Color::Rose)))
        ->toContainElement(Selectors::TEXT . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]) . '[style*="--sy-text:"][style*="--sy-dark-text:"]', 'Total');
});

it('leaves an uncoloured text and a gray one to the gray its stylesheet paints', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')))
        ->toContainElement(Selectors::TEXT . SupportSelectors::GRAY, 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->color('gray')))
        ->toContainElement(Selectors::TEXT . SupportSelectors::GRAY, 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->color('info')))
        ->toContainElement(Selectors::TEXT . SupportSelectors::color('info'), 'Total');
});

it('resolves text readable on gray 50 and, in dark mode, on gray 700, for every catalog palette', function (): void {
    $policy = $this::getContainer()->get(TextComponent::class);
    $failures = [];

    foreach (Color::all() as $name => $palette) {
        $map = $policy->getColorMap($palette);

        foreach (['text' => Color::Zinc[50], 'dark:text' => Color::Zinc[700]] as $slot => $surface) {
            if (Color::calculateContrastRatio($palette[$map[$slot]], $surface) < Color::WCAG_AA_TEXT) {
                $failures[] = "{$name} {$slot}";
            }
        }
    }

    expect($failures)->toBe([]);
});
