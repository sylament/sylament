<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Scion;

/**
 * {@see Scion}'s mapped prePersist listener, which Doctrine's default resolver
 * builds with no arguments.
 */
final class ScionTagger
{
    #[ORM\PrePersist]
    public function assignTag(Scion $scion): void
    {
        if ($scion->getTag() === '') {
            $scion->setTag($scion->getVariety() . '-graft');
        }
    }
}
