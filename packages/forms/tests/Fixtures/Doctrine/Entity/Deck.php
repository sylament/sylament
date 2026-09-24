<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

enum Deck: string
{
    case Upper = 'upper';
    case Lower = 'lower';
}
