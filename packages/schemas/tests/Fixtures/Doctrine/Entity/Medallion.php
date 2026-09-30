<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Stringable;

/**
 * A key-half target that prints blank.
 */
#[ORM\Entity]
class Medallion implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return '';
    }
}
