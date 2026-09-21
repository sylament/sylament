<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\StampAssigner;

/**
 * {@see Stamped}'s sibling, whose second identifier half a mapped entity
 * listener assigns instead of a callback of its own — the other half of the
 * mapping the class carries and a generator does not.
 */
#[ORM\Entity]
#[ORM\EntityListeners([StampAssigner::class])]
class Listened
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $scope = '';

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $stamp = '';

    #[ORM\Column]
    private string $label = '';

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getStamp(): string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): void
    {
        $this->stamp = $stamp;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
