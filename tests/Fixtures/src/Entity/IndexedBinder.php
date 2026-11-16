<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A record with a generated key whose collection holds each {@see IndexedBinderSheet}
 * under the sheet's code rather than a position, and validates each sheet, so a record
 * violation indexes the collection by a string.
 */
#[ORM\Entity]
class IndexedBinder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<string, IndexedBinderSheet>
     */
    #[ORM\OneToMany(targetEntity: IndexedBinderSheet::class, mappedBy: 'binder', cascade: ['persist'], orphanRemoval: true, indexBy: 'code')]
    #[Assert\Valid]
    public Collection $sheets;

    public function __construct()
    {
        $this->sheets = new ArrayCollection();
    }

    public function addSheet(IndexedBinderSheet $sheet): void
    {
        $this->sheets->set($sheet->code, $sheet);
        $sheet->binder = $this;
    }

    public function removeSheet(IndexedBinderSheet $sheet): void
    {
        if ($this->sheets->removeElement($sheet)) {
            $sheet->binder = null;
        }
    }
}
