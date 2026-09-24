<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a class-table hierarchy keyed by a single assigned code: the
 * primary key is the root table's, so a subclass record and a root record can
 * never share one.
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['carton' => Carton::class, 'chilled' => ChilledCarton::class])]
class Carton
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
