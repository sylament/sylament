<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The owner of {@see Purchase}, keyed by two fields: Doctrine names a single
 * join column unquoted when it compares a to-one path, so a quoted foreign key
 * is scoped only through the correlated subselect a composite owner takes.
 */
#[ORM\Entity]
class Bazaar
{
    /**
     * @var Collection<int, Purchase>
     */
    #[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'bazaar')]
    private Collection $purchases;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $region,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $code,
        #[ORM\Column]
        private string $name,
    ) {
        $this->purchases = new ArrayCollection();
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
     * @return Collection<int, Purchase>
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }
}
