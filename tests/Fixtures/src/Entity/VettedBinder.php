<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A record with a generated key whose collection of {@see VettedBinderSheet}s carries a
 * constraint of its own and validates each sheet, so a record violation can name the
 * collection, an item's field or an item's property no field maps.
 */
#[ORM\Entity]
class VettedBinder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, VettedBinderSheet>
     */
    #[ORM\OneToMany(targetEntity: VettedBinderSheet::class, mappedBy: 'binder', cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(max: 2, maxMessage: 'A binder holds two sheets at most.')]
    #[Assert\Valid]
    public Collection $sheets;

    public function __construct()
    {
        $this->sheets = new ArrayCollection();
    }

    public function addSheet(VettedBinderSheet $sheet): void
    {
        if (!$this->sheets->contains($sheet)) {
            $this->sheets->add($sheet);
            $sheet->binder = $this;
        }
    }

    public function removeSheet(VettedBinderSheet $sheet): void
    {
        if ($this->sheets->removeElement($sheet)) {
            $sheet->binder = null;
        }
    }
}
