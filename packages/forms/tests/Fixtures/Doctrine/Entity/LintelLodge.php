<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner of a cascading to-one onto a two-field assigned key.
 */
#[ORM\Entity]
class LintelLodge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $name = '';

    #[ORM\OneToOne(targetEntity: LintelCrest::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'crest_a', referencedColumnName: 'a')]
    #[ORM\JoinColumn(name: 'crest_b', referencedColumnName: 'b')]
    private ?LintelCrest $crest = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCrest(): ?LintelCrest
    {
        return $this->crest;
    }

    public function setCrest(?LintelCrest $crest): void
    {
        $this->crest = $crest;
    }
}
