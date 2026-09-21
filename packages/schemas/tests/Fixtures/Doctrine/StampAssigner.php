<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listened;

/**
 * {@see Listened}'s mapped prePersist listener, which is what puts the event on
 * the class metadata's entityListeners rather than its lifecycleCallbacks.
 */
final class StampAssigner
{
    #[ORM\PrePersist]
    public function assignStamp(Listened $listened): void
    {
        if ($listened->getStamp() === '') {
            $listened->setStamp('auto');
        }
    }
}
