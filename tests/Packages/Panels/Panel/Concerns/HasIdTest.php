<?php

declare(strict_types=1);

use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;

it('refuses a second id(), naming the first', function (): void {
    $panel = Panel::make()->id('panel-first');

    expect(fn (): Panel => $panel->id('panel-second'))->toThrow(LogicException::class, 'The panel has already been registered with the ID [panel-first].');
});

it('refuses a provider whose panel names no id()', function (): void {
    $nameless = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->path('panel-nameless');
        }
    };

    expect(fn (): array => new PanelRegistry([$nameless])->all())->toThrow(LogicException::class, 'A panel has been registered without an `id()`.');
});
