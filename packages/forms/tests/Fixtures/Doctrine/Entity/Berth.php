<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Berth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $pier = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $assignedOn = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPier(): string
    {
        return $this->pier;
    }

    public function setPier(string $pier): void
    {
        $this->pier = $pier;
    }

    public function getAssignedOn(): ?DateTimeImmutable
    {
        return $this->assignedOn;
    }

    public function setAssignedOn(?DateTimeImmutable $assignedOn): void
    {
        $this->assignedOn = $assignedOn;
    }
}
