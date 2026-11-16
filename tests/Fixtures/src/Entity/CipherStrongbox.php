<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\Cipher;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CipherType;

/**
 * A record keyed by a type that refuses its own printed form on read and on write.
 */
#[ORM\Entity]
class CipherStrongbox
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: CipherType::NAME, length: 64)]
        public Cipher $cipher,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
