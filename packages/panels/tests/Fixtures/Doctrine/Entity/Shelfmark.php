<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single identifier the application assigns — a string with no generator —
 * so a create probes the key and an edit can try to change it. It counts its
 * own constructions, because a create page that builds a throwaway record
 * before the one it maps would otherwise be invisible to a test.
 */
#[ORM\Entity]
class Shelfmark
{
    public static int $constructions = 0;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function __construct()
    {
        self::$constructions++;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = (string) $label;

        return $this;
    }
}
