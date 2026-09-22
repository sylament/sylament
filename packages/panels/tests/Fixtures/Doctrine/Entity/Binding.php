<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single-identifier child of the scalar pair {@see Imprint}: the one-to-many
 * arm under an owner keyed by two plain fields, with both join columns left
 * nullable so the association can be dissociated as well as attached.
 */
#[ORM\Entity]
class Binding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\ManyToOne(targetEntity: Imprint::class, inversedBy: 'bindings')]
    #[ORM\JoinColumn(name: 'imprint_house', referencedColumnName: 'house')]
    #[ORM\JoinColumn(name: 'imprint_year', referencedColumnName: 'year')]
    private ?Imprint $imprint = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getImprint(): ?Imprint
    {
        return $this->imprint;
    }

    public function setImprint(?Imprint $imprint): static
    {
        $this->imprint = $imprint;

        return $this;
    }
}
