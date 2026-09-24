<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner of {@see Coupon}s, whose keys the coupons draw for
 * themselves as they are inserted.
 */
#[ORM\Entity]
class Raffle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $name = '';

    /**
     * @var Collection<int, Coupon>
     */
    #[ORM\OneToMany(targetEntity: Coupon::class, mappedBy: 'raffle', cascade: ['persist'], orphanRemoval: true)]
    private Collection $coupons;

    public function __construct()
    {
        $this->coupons = new ArrayCollection();
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
     * @return Collection<int, Coupon>
     */
    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function addCoupon(Coupon $coupon): void
    {
        if (! $this->coupons->contains($coupon)) {
            $this->coupons->add($coupon);
            $coupon->setRaffle($this);
        }
    }

    public function removeCoupon(Coupon $coupon): void
    {
        $this->coupons->removeElement($coupon);
    }
}
