<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('drops its container when not contained, and is contained otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->contained(false)))
        ->toContainElement('section' . SupportSelectors::NOT_CONTAINED, 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')))
        ->toContainElement('section:not(' . SupportSelectors::NOT_CONTAINED . ')', 'Shipping');
});

it('keeps the containment its closure answered first on every later render', function (): void {
    $contained = false;
    $section = Section::make('Shipping')->contained(function () use (&$contained): bool {
        return $contained;
    });

    $first = $this->renderSchemaComponent($section);
    $contained = true;

    expect($first)->toContainElement('section' . SupportSelectors::NOT_CONTAINED, 'Shipping')
        ->and($this->renderSchemaComponent($section))
        ->toContainElement('section' . SupportSelectors::NOT_CONTAINED, 'Shipping');
});
