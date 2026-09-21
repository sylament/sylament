<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier one of whose fields is literally named `id` — the
 * translation-table shape an action mints a partial key for when it reads a
 * record's `id` property and calls it the whole one.
 */
#[ORM\Entity]
class Edition
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\Id]
    #[ORM\Column(length: 12)]
    private string $locale = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
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
}
