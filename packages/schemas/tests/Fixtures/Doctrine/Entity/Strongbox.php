<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CipherType;

/**
 * A single identifier of the doubly-strict type, which refuses its printed
 * form on read and on write alike, so no key this record mints decodes. It is
 * only ever the target of {@see StrongboxSeal}'s identity.
 */
#[ORM\Entity]
class Strongbox
{
    #[ORM\Id]
    #[ORM\Column(type: CipherType::NAME)]
    private ?Cipher $cipher = null;

    #[ORM\Column]
    private string $label = '';

    public function getCipher(): ?Cipher
    {
        return $this->cipher;
    }

    public function setCipher(Cipher $cipher): void
    {
        $this->cipher = $cipher;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
