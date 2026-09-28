<?php

declare(strict_types=1);

namespace Sylament\Testing\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 64)]
        private string $title,
        #[ORM\ManyToOne(targetEntity: Shelf::class)]
        #[ORM\JoinColumn(nullable: false)]
        private Shelf $shelf,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getShelf(): Shelf
    {
        return $this->shelf;
    }
}
