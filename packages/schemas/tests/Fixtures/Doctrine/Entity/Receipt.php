<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * A single identifier of Symfony's own ulid type.
 */
#[ORM\Entity]
class Receipt
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private ?Ulid $id = null;

    #[ORM\Column(length: 64)]
    private string $label = '';

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function setId(Ulid $id): void
    {
        $this->id = $id;
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
