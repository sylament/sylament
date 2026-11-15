<?php

declare(strict_types=1);

use Sylament\Support\Icons\IconManager;

it('refuses to render an icon without a renderer, naming the UX Icons bundle', function (): void {
    expect(fn (): string => new IconManager()->renderIcon('support:star'))
        ->toThrow(LogicException::class, 'Icon rendering requires symfony/ux-icons; register "Symfony\UX\Icons\UXIconsBundle" in your kernel.');
});
