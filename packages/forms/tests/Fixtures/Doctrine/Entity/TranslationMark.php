<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child of a record whose own identifier is an association plus a scalar, so
 * its foreign key spans both of the owner's identifier columns. The mapped-by
 * field is a plain association rather than half the child's identity, which is
 * what lets a to-many field re-point one and a repeater build one.
 */
#[ORM\Entity]
class TranslationMark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PostTranslation::class, inversedBy: 'marks')]
    #[ORM\JoinColumn(name: 'translation_post_id', referencedColumnName: 'post_id')]
    #[ORM\JoinColumn(name: 'translation_locale', referencedColumnName: 'locale')]
    private ?PostTranslation $translation = null;

    #[ORM\Column]
    private string $body = '';

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTranslation(): ?PostTranslation
    {
        return $this->translation;
    }

    public function setTranslation(?PostTranslation $translation): void
    {
        $this->translation = $translation;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
