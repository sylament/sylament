<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single-identifier record whose owner is a single derived identity: the
 * second shape the interim guards refuse, and the one Doctrine reports as
 * non-composite. Its join column names the owner's own identifier column,
 * which is an association's.
 */
#[ORM\Entity]
class ColophonLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $text = '';

    #[ORM\ManyToOne(targetEntity: Colophon::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(name: 'colophon_catalogue_id', referencedColumnName: 'catalogue_id')]
    private ?Colophon $colophon = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getColophon(): ?Colophon
    {
        return $this->colophon;
    }

    public function setColophon(?Colophon $colophon): static
    {
        $this->colophon = $colophon;

        return $this;
    }
}
