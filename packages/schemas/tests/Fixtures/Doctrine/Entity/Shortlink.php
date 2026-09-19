<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single string identifier, which a record key leaves verbatim whatever it
 * contains.
 */
#[ORM\Entity]
class Shortlink
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $code = '';

    #[ORM\Column]
    private string $target = '';

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }
}
