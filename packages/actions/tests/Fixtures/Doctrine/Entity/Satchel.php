<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The owner of a many-to-many onto {@see Charm}, whose collection a
 * relation-scoped create writes before the charm is persisted.
 */
#[ORM\Entity]
class Satchel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Charm>
     */
    #[ORM\ManyToMany(targetEntity: Charm::class)]
    #[ORM\JoinTable(name: 'satchel_charm')]
    #[ORM\JoinColumn(name: 'satchel_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'charm_name', referencedColumnName: 'name')]
    private Collection $charms;

    public function __construct()
    {
        $this->charms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Charm>
     */
    public function getCharms(): Collection
    {
        return $this->charms;
    }

    public function addCharm(Charm $charm): void
    {
        if (! $this->charms->contains($charm)) {
            $this->charms->add($charm);
        }
    }

    public function removeCharm(Charm $charm): void
    {
        $this->charms->removeElement($charm);
    }
}
