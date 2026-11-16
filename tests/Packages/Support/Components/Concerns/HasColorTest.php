<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Icon;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders in the default colour unless a colour is set', function (): void {
    expect($this->renderSchemaComponent(Icon::make('heroicons:check-circle')->defaultColor('info')))
        ->toContainElement(SchemasSelectors::ICON . SupportSelectors::color('info'), 'check-circle')
        ->and($this->renderSchemaComponent(Icon::make('heroicons:check-circle')->defaultColor('info')->color('danger')))
        ->toContainElement(SchemasSelectors::ICON . SupportSelectors::color('danger'), 'check-circle')
        ->not->toContainElement(SupportSelectors::color('info'));
});
