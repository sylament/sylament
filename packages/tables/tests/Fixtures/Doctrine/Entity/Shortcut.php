<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two string fields, seeded with the characters a row key has
 * to survive — a dot, a slash, a space, the separator itself and non-ASCII — so
 * a key that does not escape them is caught.
 */
#[ORM\Entity]
class Shortcut
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $host = '';

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $path = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = false;

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
