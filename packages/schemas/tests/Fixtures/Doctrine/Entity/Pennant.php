<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * {@see Ribbon} with its two identifier fields the other way round, the tag
 * first — so the refusal's field is the tag in both orders.
 */
#[ORM\Entity]
class Pennant
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tag::class)]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    private ?Tag $tag = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Binder::class, inversedBy: 'pennants')]
    #[ORM\JoinColumn(name: 'binder_id', referencedColumnName: 'id')]
    private ?Binder $binder = null;

    #[ORM\Column]
    private string $label = '';

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): void
    {
        $this->tag = $tag;
    }

    public function getBinder(): ?Binder
    {
        return $this->binder;
    }

    public function setBinder(?Binder $binder): void
    {
        $this->binder = $binder;
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
