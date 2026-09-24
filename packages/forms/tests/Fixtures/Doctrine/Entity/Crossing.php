<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Crossing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'crossings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ferry $ferry = null;

    #[ORM\Column]
    private string $route = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $departsOn = null;

    #[ORM\Column(length: 16, nullable: true, enumType: Deck::class)]
    private ?Deck $deck = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFerry(): ?Ferry
    {
        return $this->ferry;
    }

    public function setFerry(?Ferry $ferry): void
    {
        $this->ferry = $ferry;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function getDepartsOn(): ?DateTimeImmutable
    {
        return $this->departsOn;
    }

    public function setDepartsOn(?DateTimeImmutable $departsOn): void
    {
        $this->departsOn = $departsOn;
    }

    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    public function setDeck(?Deck $deck): void
    {
        $this->deck = $deck;
    }
}
