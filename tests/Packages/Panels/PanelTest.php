<?php

declare(strict_types=1);

use Sylament\Panels\Panel;

it('configures a panel before its id is set, so a configureUsing() closure reading the id throws', function (): void {
    expect(fn (): mixed => Panel::configureUsing(fn (Panel $panel): string => $panel->getId(), during: fn (): Panel => Panel::make()->id('panel-configured')))
        ->toThrow(LogicException::class, 'A panel has been registered without an `id()`.');
});
