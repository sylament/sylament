<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

/**
 * A tone backed by the colour a swatch paints: `Indigo` by a hex value, `BrandBlue` by a name
 * no CSS colour syntax admits.
 */
enum SwatchTone: string
{
    case Indigo = '#4f46e5';
    case BrandBlue = 'brand-blue';
}
