<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The ambiguous child: a leaf reachable from a {@see Tome} through either of
 * two associations, one of them optional.
 */
#[ORM\Entity]
class Leaf
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\ManyToOne(targetEntity: Tome::class, inversedBy: 'leaves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tome $tome = null;

    #[ORM\ManyToOne(targetEntity: Tome::class, inversedBy: 'appendixLeaves')]
    private ?Tome $appendixTome = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTome(): ?Tome
    {
        return $this->tome;
    }

    public function setTome(?Tome $tome): static
    {
        $this->tome = $tome;

        return $this;
    }

    public function getAppendixTome(): ?Tome
    {
        return $this->appendixTome;
    }

    public function setAppendixTome(?Tome $appendixTome): static
    {
        $this->appendixTome = $appendixTome;

        return $this;
    }
}
