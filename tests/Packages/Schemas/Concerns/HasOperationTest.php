<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\InjectionForm;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Twig\Error\RuntimeError;

it('runs a nested schema under the operation of the schema around it', function (): void {
    $schema = Schema::make()->operation('edit')->components([
        Section::make('Billing')->components([Text::make(static fn (string $operation): string => "Nested {$operation}")]),
    ]);

    expect($this->renderViewComponent($schema))->toContainElement(Selectors::TEXT, 'Nested edit');
});

it('runs under its host class when nothing names an operation', function (): void {
    expect($this->createLiveComponent(InjectionForm::class)->render()->crawler())
        ->toContainElement(Selectors::TEXT, 'Run as ' . InjectionForm::class);
});

it('refuses to name an operation when nothing names one and no host holds it', function (): void {
    expect(fn () => $this->renderViewComponent(Schema::make()->components([Text::make(static fn (string $operation): string => $operation)])))
        ->toThrow(RuntimeError::class, 'Schema is not part of LiveComponent');
});
