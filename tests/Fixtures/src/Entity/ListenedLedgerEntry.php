<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\LedgerFolioListener;

/**
 * A record keyed by two strings whose mapped entity listener fills a blank folio.
 */
#[ORM\Entity]
#[ORM\EntityListeners([LedgerFolioListener::class])]
class ListenedLedgerEntry
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $book,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $folio,
    ) {
    }
}
