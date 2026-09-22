<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A joined subclass: its own table keys each row by the root's identifier
 * column, `ledger_id`, and holds only what the subclass adds.
 */
#[ORM\Entity]
class LedgerDraft extends Ledger
{
    #[ORM\Column]
    private string $revision = '';

    public function getRevision(): string
    {
        return $this->revision;
    }

    public function setRevision(string $revision): void
    {
        $this->revision = $revision;
    }
}
