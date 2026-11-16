<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * A record keyed by symfony/uid's own uuid and ulid types.
 */
#[ORM\Entity]
class UidReceipt
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME)]
        public Uuid $till,
        #[ORM\Id]
        #[ORM\Column(type: UlidType::NAME)]
        public Ulid $receipt,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
