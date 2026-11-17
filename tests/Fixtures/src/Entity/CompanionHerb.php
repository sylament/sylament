<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A herb holding a one-way many-to-many onto its own class, so owner and target share
 * one table, with two text columns a search can tell apart.
 */
#[ORM\Entity]
class CompanionHerb
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, CompanionHerb>
     */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'companion_herb_companion')]
    #[ORM\JoinColumn(name: 'herb_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'companion_id', referencedColumnName: 'id')]
    public Collection $companions;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
        #[ORM\Column]
        public string $latinName = '',
    ) {
        $this->companions = new ArrayCollection();
    }

    public function addCompanion(self $companion): void
    {
        $this->companions->add($companion);
    }

    public function removeCompanion(self $companion): void
    {
        $this->companions->removeElement($companion);
    }
}
