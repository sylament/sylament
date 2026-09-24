<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner cascading persist onto sigils of either class of their
 * single-table hierarchy, so one walk can reach two new records of one root.
 */
#[ORM\Entity]
class SigilRack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Sigil>
     */
    #[ORM\ManyToMany(targetEntity: Sigil::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'SigilRack_Sigil')]
    #[ORM\JoinColumn(name: 'rack_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'sigil_realm', referencedColumnName: 'realm')]
    #[ORM\InverseJoinColumn(name: 'sigil_mark', referencedColumnName: 'mark')]
    private Collection $sigils;

    public function __construct()
    {
        $this->sigils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Sigil>
     */
    public function getSigils(): Collection
    {
        return $this->sigils;
    }

    public function addSigil(Sigil $sigil): void
    {
        $this->sigils->add($sigil);
    }
}
