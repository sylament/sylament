<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a joined hierarchy whose identifier field is not named like its
 * column, so a subclass's table carries the root's column name — the name a
 * statement over that table has to use.
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['ledger' => Ledger::class, 'draft' => LedgerDraft::class])]
class Ledger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ledger_id')]
    private ?int $id = null;

    #[ORM\Column]
    private string $label = '';

    public function getId(): ?int
    {
        return $this->id;
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
