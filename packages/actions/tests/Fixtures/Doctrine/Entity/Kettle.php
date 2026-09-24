<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A derived identity whose identifier association is the relationship a create
 * attaches, constrained by the application to have one — which only a record
 * check that runs after the attach can see satisfied.
 */
#[ORM\Entity]
class Kettle
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Pantry::class, inversedBy: 'kettles')]
    #[ORM\JoinColumn(name: 'pantry_id', referencedColumnName: 'id')]
    #[Assert\NotNull]
    private ?Pantry $pantry = null;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function getPantry(): ?Pantry
    {
        return $this->pantry;
    }

    public function setPantry(?Pantry $pantry): void
    {
        $this->pantry = $pantry;
    }

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
