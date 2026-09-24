<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\BinaryUuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Logbook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $captain = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $openedOn = null;

    #[ORM\Column(length: 16, nullable: true, enumType: Zone::class)]
    private ?Zone $zone = null;

    #[ORM\Column(type: BinaryUuidType::NAME, nullable: true)]
    private ?Uuid $seal = null;

    #[ORM\ManyToOne]
    private ?Author $keeper = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCaptain(): string
    {
        return $this->captain;
    }

    public function setCaptain(string $captain): void
    {
        $this->captain = $captain;
    }

    public function getOpenedOn(): ?DateTimeImmutable
    {
        return $this->openedOn;
    }

    public function setOpenedOn(?DateTimeImmutable $openedOn): void
    {
        $this->openedOn = $openedOn;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): void
    {
        $this->zone = $zone;
    }

    public function getSeal(): ?Uuid
    {
        return $this->seal;
    }

    public function setSeal(?Uuid $seal): void
    {
        $this->seal = $seal;
    }

    public function getKeeper(): ?Author
    {
        return $this->keeper;
    }

    public function setKeeper(?Author $keeper): void
    {
        $this->keeper = $keeper;
    }
}
