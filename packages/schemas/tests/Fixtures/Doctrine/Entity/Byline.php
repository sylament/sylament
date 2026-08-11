<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The related record of an association mapped **without** `orphanRemoval`, so a
 * switched-off entanglement has nothing but the explicit removal to delete it.
 */
#[ORM\Entity]
class Byline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?string $credit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCredit(): ?string
    {
        return $this->credit;
    }

    public function setCredit(?string $credit): void
    {
        $this->credit = $credit;
    }
}
