<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier one of whose fields is literally named `id` — the
 * translation-table shape, and the one where reading that field alone mints a
 * key naming half a record.
 */
#[ORM\Entity]
class Caption
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\Id]
    #[ORM\Column(length: 12)]
    private string $locale = '';

    #[ORM\Column(length: 255)]
    private string $text = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }
}
