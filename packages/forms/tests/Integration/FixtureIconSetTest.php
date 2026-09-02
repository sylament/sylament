<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Sylament\Support\Twig\Extension\IconExtension;

it('renders the glyphs the sweep added to the fixture icon set', function (string $icon): void {
    $extension = $this::getContainer()->get('sylament_support.icon_extension');
    expect($extension)->toBeInstanceOf(IconExtension::class);

    expect((string) $extension->generateIconHtml($icon))->toContain('<svg');
})->with([
    'heroicons:eye-slash-20-solid',
    'heroicons:clipboard-document-list-20-solid',
    'heroicons:x-mark-16-solid',
]);
