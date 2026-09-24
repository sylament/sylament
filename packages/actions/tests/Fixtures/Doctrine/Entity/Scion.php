<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Actions\Tests\Fixtures\Doctrine\ScionTagger;

/**
 * {@see Seedling}'s sibling, whose single assigned string key a mapped entity
 * listener fills when it is still blank rather than a callback of its own — so
 * the prePersist hook sits on the class metadata's entity listeners.
 */
#[ORM\Entity]
#[ORM\EntityListeners([ScionTagger::class])]
class Scion
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
}
