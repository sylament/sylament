<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string key that a prePersist callback fills from its
 * variety when it is still blank, so a record left unkeyed until `persist()`
 * is complete by the time it is written.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Seedling
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $tag = '';

    #[ORM\Column(length: 255)]
    private string $variety = '';

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    public function getVariety(): string
    {
        return $this->variety;
    }

    public function setVariety(string $variety): void
    {
        $this->variety = $variety;
    }

    #[ORM\PrePersist]
    public function label(): void
    {
        if ($this->tag === '') {
            $this->tag = $this->variety . '-sprout';
        }
    }
}
