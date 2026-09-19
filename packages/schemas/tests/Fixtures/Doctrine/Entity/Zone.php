<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

enum Zone: string
{
    case Header = 'header';
    case Footer = 'footer';
}
