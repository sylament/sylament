<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

it('offers its text as a control that copies it, operated from the keyboard, and none unless copyable', function (): void {
    $copyable = $this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable());
    $plain = $this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1']));

    expect($copyable)->toContainElement('[role="button"]' . SupportSelectors::CLIPBOARD_CONTROLLER . ':not([tabindex])', 'L-1')
        ->and($copyable->filter(SupportSelectors::CLIPBOARD_COPY)->count())->toBe(1)
        ->and($plain)->toContainElement(TablesSelectors::TEXT_COLUMN, 'L-1')
        ->and($plain->filter(SupportSelectors::CLIPBOARD_CONTROLLER)->count())->toBe(0);
});

it('copies the text the cell shows, unless copyableState() answers another', function (): void {
    $shown = TextColumn::make('sku')->record(['sku' => 'l-1'])->formatStateUsing(fn (string $state): string => mb_strtoupper($state))->copyable();
    $raw = TextColumn::make('sku')->record(['sku' => 'l-1'])->formatStateUsing(fn (string $state): string => mb_strtoupper($state))->copyable()->copyableState(fn (string $state): string => $state);

    expect($this->renderViewComponent($shown))
        ->toContainElement(SupportSelectors::clipboardValue('text', 'L-1'), 'L-1')
        ->and($this->renderViewComponent($raw))
        ->toContainElement(SupportSelectors::clipboardValue('text', 'l-1'), 'L-1');
});

it('confirms a copy with the message it is given, Copied otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()->copyMessage('SKU copied')))
        ->toContainElement(SupportSelectors::clipboardValue('message', 'SKU copied'), 'L-1')
        ->and($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()))
        ->toContainElement(SupportSelectors::clipboardValue('message', 'Copied'), 'L-1');
});

it('shows its confirmation for the milliseconds it is given, two seconds otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()->copyMessageDuration(5000)))
        ->toContainElement(SupportSelectors::clipboardValue('duration', '5000'), 'L-1')
        ->and($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()))
        ->toContainElement(SupportSelectors::clipboardValue('duration', '2000'), 'L-1');
});
