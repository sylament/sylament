<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner of a cascading to-one onto an {@see Emblem}, so a
 * singular relationship can construct the emblem and a select can create one.
 */
#[ORM\Entity]
class Guild
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $name = '';

    #[ORM\OneToOne(targetEntity: Emblem::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'emblem_code', referencedColumnName: 'code')]
    private ?Emblem $emblem = null;

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

    public function getEmblem(): ?Emblem
    {
        return $this->emblem;
    }

    public function setEmblem(?Emblem $emblem): void
    {
        $this->emblem = $emblem;
    }
}
