<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Entity\ListenedRegisterLine;

/**
 * The entity listener {@see ListenedRegisterLine} maps: it fills a blank line.
 */
final class RegisterLineListener
{
    #[ORM\PrePersist]
    public function fillLine(ListenedRegisterLine $line): void
    {
        if ($line->line === '') {
            $line->line = 'auto';
        }
    }
}
