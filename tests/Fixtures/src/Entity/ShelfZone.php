<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

enum ShelfZone: string
{
    case Header = 'header';
    case Footer = 'footer';
}
