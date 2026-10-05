<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar pair the constructor sets, lower-cased on the way in, and no setter
 * can change, beside one ordinary field: a create page must build it in
 * makeRecord(), its key fields map onto properties the mapper may only leave
 * alone, and a key typed in capitals is one the constructor keeps differently.
 */
#[ORM\Entity]
class Watermark
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private readonly string $mill;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private readonly string $pattern;

    #[ORM\Column(length: 255)]
    private string $note = '';

    public function __construct(string $mill, string $pattern)
    {
        $this->mill = mb_strtolower($mill);
        $this->pattern = mb_strtolower($pattern);
    }

    public function getMill(): string
    {
        return $this->mill;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = (string) $note;

        return $this;
    }
}
