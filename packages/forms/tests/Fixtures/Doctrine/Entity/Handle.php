<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record with a unique column and a second column to scope a probe by, which
 * the four existing fixture entities do not have.
 */
#[ORM\Entity]
class Handle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 64, unique: true)]
        private string $name,
        #[ORM\Column(length: 64, nullable: true)]
        private ?string $owner = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }
}
