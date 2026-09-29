<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A composite identifier of Symfony's own uuid type beside an int.
 */
#[ORM\Entity]
class Parcel
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $batch = null;

    #[ORM\Id]
    #[ORM\Column]
    private int $seq = 0;

    #[ORM\Column(length: 64)]
    private string $label = '';

    public function getBatch(): ?Uuid
    {
        return $this->batch;
    }

    public function setBatch(Uuid $batch): void
    {
        $this->batch = $batch;
    }

    public function getSeq(): int
    {
        return $this->seq;
    }

    public function setSeq(int $seq): void
    {
        $this->seq = $seq;
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
