<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity onto {@see TrestleOwner}: its key is the owner's.
 */
#[ORM\Entity]
class TrestleTop
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: TrestleOwner::class, inversedBy: 'top')]
    #[ORM\JoinColumn(name: 'owner_code', referencedColumnName: 'code')]
    private ?TrestleOwner $owner = null;

    #[ORM\Column]
    private string $label = '';

    public function getOwner(): ?TrestleOwner
    {
        return $this->owner;
    }

    public function setOwner(?TrestleOwner $owner): void
    {
        $this->owner = $owner;
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
