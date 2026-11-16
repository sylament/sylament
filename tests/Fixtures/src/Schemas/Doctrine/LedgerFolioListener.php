<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Entity\ListenedLedgerEntry;

/**
 * The entity listener {@see ListenedLedgerEntry} maps: it fills a blank folio.
 */
final class LedgerFolioListener
{
    #[ORM\PrePersist]
    public function fillFolio(ListenedLedgerEntry $entry): void
    {
        if ($entry->folio === '') {
            $entry->folio = 'auto';
        }
    }
}
