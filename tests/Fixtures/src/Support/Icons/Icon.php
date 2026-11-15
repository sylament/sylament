<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support\Icons;

/**
 * An icon named by its value, drawn the same at every size.
 */
enum Icon: string
{
    case Star = 'support:star';
}
