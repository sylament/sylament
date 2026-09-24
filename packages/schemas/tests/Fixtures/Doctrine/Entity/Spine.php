<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string identifier reached through a cascading to-one, so
 * a new spine is inserted by the flush of the binder that holds it.
 */
#[ORM\Entity]
class Spine
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\Column]
    private string $label = '';

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
