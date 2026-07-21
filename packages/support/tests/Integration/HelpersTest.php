<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration;

use Sylament\Support\Enum\IconSize;
use Sylament\Support\Icons\IconManager;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Twig\Markup;

use function Sylament\Support\generate_icon_html;

it('renders a local icon through the real ux-icons renderer', function (): void {
    $this::getContainer();

    $html = (string) generate_icon_html('heroicons:x-mark');

    expect($html)->toContain('<svg')
        ->toContain('class="sy-icon sy-size-md"')
        ->toContain('aria-hidden="true"');
});

it('applies an explicit icon size class', function (): void {
    $this::getContainer();

    expect((string) generate_icon_html('heroicons:x-mark', size: IconSize::Large))
        ->toContain('class="sy-icon sy-size-lg"');
});

it('renders an icon resolved from a registered alias', function (): void {
    $this::getContainer();
    IconManager::getInstance()->register(['test.alias' => 'heroicons:x-mark']);

    expect((string) generate_icon_html(null, 'test.alias'))->toContain('<svg');
});

it('throws for an unknown icon because iconify on-demand is disabled', function (): void {
    $this::getContainer();

    expect(fn (): ?Markup => generate_icon_html('heroicons:does-not-exist'))
        ->toThrow(IconNotFoundException::class);
});
