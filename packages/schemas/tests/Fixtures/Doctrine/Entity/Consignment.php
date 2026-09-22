<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar pair in a table and columns named by reserved words, every one
 * quoted in the mapping, so a statement naming them bare is not SQL.
 */
#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Consignment
{
    #[ORM\Id]
    #[ORM\Column(name: '`where`', length: 32)]
    private string $origin = '';

    #[ORM\Id]
    #[ORM\Column(name: '`having`', length: 32)]
    private string $holder = '';

    #[ORM\Column]
    private string $label = '';

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): void
    {
        $this->origin = $origin;
    }

    public function getHolder(): string
    {
        return $this->holder;
    }

    public function setHolder(string $holder): void
    {
        $this->holder = $holder;
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
