<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Colors\Components;

use Sylament\Schemas\Colors\Components\IconComponent;
use Sylament\Support\Colors\Color;

/**
 * An icon is decorative beside the text it labels, so it keeps the shade the stylesheet gives
 * it; the two base classes `getComponentClasses()` emits before consulting the map are the whole
 * contribution.
 */
it('declares no slots, whatever palette it is handed', function (): void {
    expect(new IconComponent()->getColorMap(Color::Amber))->toBe([]);
});
