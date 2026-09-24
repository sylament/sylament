<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner of two join-entity collections onto {@see Band}, keyed
 * by the same two associations declared in both orders: a {@see Booking} names
 * its festival first, a {@see Headliner} its band.
 */
#[ORM\Entity]
class Festival
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $name = '';

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'festival', cascade: ['persist'], orphanRemoval: true)]
    private Collection $bookings;

    /**
     * @var Collection<int, Headliner>
     */
    #[ORM\OneToMany(targetEntity: Headliner::class, mappedBy: 'festival', cascade: ['persist'], orphanRemoval: true)]
    private Collection $headliners;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->headliners = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): void
    {
        if (! $this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setFestival($this);
        }
    }

    public function removeBooking(Booking $booking): void
    {
        $this->bookings->removeElement($booking);
    }

    /**
     * @return Collection<int, Headliner>
     */
    public function getHeadliners(): Collection
    {
        return $this->headliners;
    }

    public function addHeadliner(Headliner $headliner): void
    {
        if (! $this->headliners->contains($headliner)) {
            $this->headliners->add($headliner);
            $headliner->setFestival($this);
        }
    }

    public function removeHeadliner(Headliner $headliner): void
    {
        $this->headliners->removeElement($headliner);
    }
}
