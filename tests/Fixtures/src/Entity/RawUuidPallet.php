<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\BinaryUuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A record keyed by a raw-bytes uuid beside a string.
 */
#[ORM\Entity]
class RawUuidPallet
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: BinaryUuidType::NAME)]
        public Uuid $batch,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $lane,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
