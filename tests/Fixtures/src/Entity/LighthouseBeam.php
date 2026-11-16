<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

enum LighthouseBeam: string
{
    case Fixed = 'fixed';
    case Flashing = 'flashing';
}
