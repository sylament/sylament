<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A generated-id owner of two cascading to-ones: one onto a two-field assigned
 * key, and one onto a record whose own constraints the holder's validation
 * reaches.
 */
#[ORM\Entity]
class LintelHolder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    #[ORM\OneToOne(targetEntity: LintelPair::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'pair_a', referencedColumnName: 'a')]
    #[ORM\JoinColumn(name: 'pair_b', referencedColumnName: 'b')]
    private ?LintelPair $pair = null;

    #[ORM\OneToOne(targetEntity: LintelPlate::class, cascade: ['persist'])]
    #[Assert\Valid]
    private ?LintelPlate $plate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPair(): ?LintelPair
    {
        return $this->pair;
    }

    public function setPair(?LintelPair $pair): void
    {
        $this->pair = $pair;
    }

    public function getPlate(): ?LintelPlate
    {
        return $this->plate;
    }

    public function setPlate(?LintelPlate $plate): void
    {
        $this->plate = $plate;
    }
}
