<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SectionNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PostSection $section = null;

    #[ORM\Column]
    private string $body = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): ?PostSection
    {
        return $this->section;
    }

    public function setSection(PostSection $section): void
    {
        $this->section = $section;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
