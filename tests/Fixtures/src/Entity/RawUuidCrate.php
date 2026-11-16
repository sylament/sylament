<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\BinaryUuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A record keyed by a uuid its column holds as sixteen raw bytes.
 */
#[ORM\Entity]
class RawUuidCrate
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: BinaryUuidType::NAME)]
        public Uuid $id,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
