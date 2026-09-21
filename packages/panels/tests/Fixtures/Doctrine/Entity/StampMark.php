<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single-identifier record whose owner is composite: the shape a nested
 * resource and a relation manager refuse until owner scoping is built. The two
 * join columns are named per identifier column because a to-one onto a
 * composite target otherwise has a single `id` guessed for it.
 */
#[ORM\Entity]
class StampMark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Stamp::class, inversedBy: 'marks')]
    #[ORM\JoinColumn(name: 'stamp_catalogue_id', referencedColumnName: 'catalogue_id')]
    #[ORM\JoinColumn(name: 'stamp_code', referencedColumnName: 'code')]
    private ?Stamp $stamp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getStamp(): ?Stamp
    {
        return $this->stamp;
    }

    public function setStamp(?Stamp $stamp): static
    {
        $this->stamp = $stamp;

        return $this;
    }
}
