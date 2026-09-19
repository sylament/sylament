<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single `guid` identifier on a platform that declares no guid type of its
 * own, so the column is a plain CHAR(36) and holds whatever a record put in it.
 */
#[ORM\Entity]
class Beacon
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id = '';

    #[ORM\Column]
    private string $label = '';

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
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
