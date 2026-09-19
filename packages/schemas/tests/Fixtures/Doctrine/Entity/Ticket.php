<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A `guid` identifier and a `bigint` one: the two types whose own conversion
 * hands a malformed string back untouched.
 */
#[ORM\Entity]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id = '';

    #[ORM\Id]
    #[ORM\Column(type: Types::BIGINT)]
    private int|string $serial = 0;

    #[ORM\Column]
    private string $subject = '';

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSerial(): int|string
    {
        return $this->serial;
    }

    public function setSerial(int|string $serial): void
    {
        $this->serial = $serial;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }
}
