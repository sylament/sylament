<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string identifier that starts as `''`, the whole identity
 * of the {@see Locket} built around it.
 */
#[ORM\Entity]
class Clasp
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $code = '';

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}
