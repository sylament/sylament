<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A cached owner keyed by two fields, so its lamps' foreign key spans two
 * columns and a by-key dissociate from it is one DBAL statement.
 */
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Stand
{
    /**
     * @var Collection<int, Lamp>
     */
    #[ORM\OneToMany(targetEntity: Lamp::class, mappedBy: 'stand')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $lamps;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $region,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $code,
        #[ORM\Column(length: 64)]
        private string $name,
    ) {
        $this->lamps = new ArrayCollection();
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Lamp>
     */
    public function getLamps(): Collection
    {
        return $this->lamps;
    }
}
