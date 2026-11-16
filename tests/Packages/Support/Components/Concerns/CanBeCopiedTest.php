<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;

it('offers its text as a control that copies it, operated from the keyboard, and none unless copyable', function (): void {
    $copyable = $this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable());
    $plain = $this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1']));

    expect($copyable)->toContainElement('[role="button"][data-controller="sylament--support--clipboard"]:not([tabindex])', 'L-1')
        ->and($copyable->filter('[data-action~="click->sylament--support--clipboard#copy"][data-action~="keydown.enter->sylament--support--clipboard#copy"][data-action~="keydown.space->sylament--support--clipboard#copy"]')->count())->toBe(1)
        ->and($plain)->toContainElement('.sy', 'L-1')
        ->and($plain->filter('[data-controller~="sylament--support--clipboard"]')->count())->toBe(0);
});

it('copies the text the cell shows, unless copyableState() answers another', function (): void {
    $shown = TextColumn::make('sku')->record(['sku' => 'l-1'])->formatStateUsing(fn (string $state): string => mb_strtoupper($state))->copyable();
    $raw = TextColumn::make('sku')->record(['sku' => 'l-1'])->formatStateUsing(fn (string $state): string => mb_strtoupper($state))->copyable()->copyableState(fn (string $state): string => $state);

    expect($this->renderViewComponent($shown))
        ->toContainElement('[data-sylament--support--clipboard-text-value="L-1"]', 'L-1')
        ->and($this->renderViewComponent($raw))
        ->toContainElement('[data-sylament--support--clipboard-text-value="l-1"]', 'L-1');
});

it('confirms a copy with the message it is given, Copied otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()->copyMessage('SKU copied')))
        ->toContainElement('[data-sylament--support--clipboard-message-value="SKU copied"]', 'L-1')
        ->and($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()))
        ->toContainElement('[data-sylament--support--clipboard-message-value="Copied"]', 'L-1');
});

it('shows its confirmation for the milliseconds it is given, two seconds otherwise', function (): void {
    expect($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()->copyMessageDuration(5000)))
        ->toContainElement('[data-sylament--support--clipboard-duration-value="5000"]', 'L-1')
        ->and($this->renderViewComponent(TextColumn::make('sku')->record(['sku' => 'L-1'])->copyable()))
        ->toContainElement('[data-sylament--support--clipboard-duration-value="2000"]', 'L-1');
});
