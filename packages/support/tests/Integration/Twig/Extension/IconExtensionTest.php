<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration\Twig\Extension;

use Sylament\Support\Icons\IconManager;
use Sylament\Support\Twig\Extension\IconExtension;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Twig\Markup;

function containerIconExtension(): IconExtension
{
    $extension = test()->getContainer()->get('sylament_support.icon_extension');

    expect($extension)->toBeInstanceOf(IconExtension::class);

    return $extension;
}

it('renders a local icon through the real ux-icons renderer', function (): void {
    $html = (string) containerIconExtension()->generateIconHtml('heroicons:x-mark');

    expect($html)->toContain('<svg')
        ->toContain('class="sy sy-icon sy-size-md"')
        ->toContain('aria-hidden="true"');
});

it('applies an explicit icon size class', function (): void {
    expect((string) containerIconExtension()->generateIconHtml('heroicons:x-mark', size: 'lg'))
        ->toContain('class="sy sy-icon sy-size-lg"');
});

it('renders an icon resolved from a registered alias', function (): void {
    $extension = containerIconExtension();

    $icons = $this::getContainer()->get(IconManager::class);
    expect($icons)->toBeInstanceOf(IconManager::class);
    $icons->register(['test.alias' => 'heroicons:x-mark']);

    expect((string) $extension->generateIconHtml(null, 'test.alias'))->toContain('<svg');
});

it('throws for an unknown icon because iconify on-demand is disabled', function (): void {
    $extension = containerIconExtension();

    expect(fn (): ?Markup => $extension->generateIconHtml('heroicons:does-not-exist'))
        ->toThrow(IconNotFoundException::class);
});
