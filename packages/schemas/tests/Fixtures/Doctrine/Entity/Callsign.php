<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A single assigned identifier that carries constraints of its own: one on the
 * key that refuses a value an edit could move it to, and two on the label that
 * both refuse a one-letter lower-case name, with the same message at the same
 * path.
 */
#[ORM\Entity]
class Callsign
{
    public const string RESERVED = 'reserved';

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    #[Assert\NotEqualTo(value: self::RESERVED, message: 'That call sign is reserved.')]
    private string $code = '';

    #[ORM\Column]
    #[Assert\Length(min: 2, minMessage: 'Name the station.')]
    #[Assert\Regex(pattern: '/^\p{Lu}/u', message: 'Name the station.')]
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
