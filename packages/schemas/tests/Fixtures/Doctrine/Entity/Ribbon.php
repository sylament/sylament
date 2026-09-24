<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A join entity keyed by the binder it hangs off and a tag, the binder first:
 * with no field of its own to type, a refusal belongs on the tag, the half that
 * does not point back.
 */
#[ORM\Entity]
class Ribbon
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Binder::class, inversedBy: 'ribbons')]
    #[ORM\JoinColumn(name: 'binder_id', referencedColumnName: 'id')]
    private ?Binder $binder = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tag::class)]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    private ?Tag $tag = null;

    #[ORM\Column]
    private string $label = '';

    public function getBinder(): ?Binder
    {
        return $this->binder;
    }

    public function setBinder(?Binder $binder): void
    {
        $this->binder = $binder;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): void
    {
        $this->tag = $tag;
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
