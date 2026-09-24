<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An owner whose one related record carries a date, an enum and a uuid — leaves
 * a singular relationship fills only through the state casts that flatten them.
 */
#[ORM\Entity]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    #[ORM\OneToOne(cascade: ['persist'])]
    private ?Logbook $logbook = null;

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

    public function getLogbook(): ?Logbook
    {
        return $this->logbook;
    }

    public function setLogbook(?Logbook $logbook): void
    {
        $this->logbook = $logbook;
    }
}
