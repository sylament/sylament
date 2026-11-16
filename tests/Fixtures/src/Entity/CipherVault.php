<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\Cipher;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CipherType;

/**
 * A record keyed by a cipher half beside a string.
 */
#[ORM\Entity]
class CipherVault
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: CipherType::NAME, length: 64)]
        public Cipher $cipher,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $zone,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
