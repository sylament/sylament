<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A chained derived identity: keyed by a seal, itself keyed by its charter, beside a string.
 */
#[ORM\Entity]
class CharterSealClause
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(referencedColumnName: 'charter_id')]
        public CharterSeal $seal,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $clause,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
