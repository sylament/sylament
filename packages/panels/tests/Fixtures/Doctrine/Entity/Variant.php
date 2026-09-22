<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single-identifier child of {@see Edition}, whose identifier's first field
 * is literally named `id` — so a parent segment spelled `5` is a plausible URL
 * for a key that is really `5~en-GB`.
 */
#[ORM\Entity]
class Variant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: Edition::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(name: 'edition_id', referencedColumnName: 'id')]
    #[ORM\JoinColumn(name: 'edition_locale', referencedColumnName: 'locale')]
    private ?Edition $edition = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }
}
