<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The deepest level of the nested-resource rig: a line of one {@see Verse},
 * which is itself a chapter's — the two-parent chain a nested URL walks.
 */
#[ORM\Entity]
class Line
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $content = '';

    #[ORM\ManyToOne(targetEntity: Verse::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Verse $verse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getVerse(): ?Verse
    {
        return $this->verse;
    }

    public function setVerse(?Verse $verse): static
    {
        $this->verse = $verse;

        return $this;
    }
}
