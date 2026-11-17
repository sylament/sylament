<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one generated integer whose many-to-many holds {@see SatchelCharm}s,
 * records keyed by what the application assigns; its adder and remover do not call the
 * charm back.
 */
#[ORM\Entity]
class CharmSatchel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, SatchelCharm>
     */
    #[ORM\ManyToMany(targetEntity: SatchelCharm::class)]
    #[ORM\JoinTable(name: 'charm_satchel_charm')]
    #[ORM\JoinColumn(name: 'satchel', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'charm_tier', referencedColumnName: 'tier')]
    #[ORM\InverseJoinColumn(name: 'charm_code', referencedColumnName: 'code')]
    public Collection $charms;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
        $this->charms = new ArrayCollection();
    }

    public function addCharm(SatchelCharm $charm): void
    {
        $this->charms->add($charm);
    }

    public function removeCharm(SatchelCharm $charm): void
    {
        $this->charms->removeElement($charm);
    }
}
