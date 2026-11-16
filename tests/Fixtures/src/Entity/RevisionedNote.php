<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record whose identifier its mapped superclass declares.
 */
#[ORM\Entity]
class RevisionedNote extends RevisionedBase
{
    #[ORM\Column]
    public string $note = '';
}
