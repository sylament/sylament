<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single boolean identifier, the one flavour whose false value a printed key
 * has to spell rather than treat as absent.
 */
#[ORM\Entity]
class Flagged
{
    #[ORM\Id]
    #[ORM\Column]
    private bool $flag = false;

    #[ORM\Column]
    private string $title = '';

    public function isFlag(): bool
    {
        return $this->flag;
    }

    public function setFlag(bool $flag): void
    {
        $this->flag = $flag;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
