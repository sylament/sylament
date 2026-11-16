<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two strings whose prePersist callback fills a blank folio and
 * leaves the book as it is.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class PrefilledLedgerEntry
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

    #[ORM\PrePersist]
    public function fillFolio(): void
    {
        if ($this->folio === '') {
            $this->folio = 'auto';
        }
    }
}
