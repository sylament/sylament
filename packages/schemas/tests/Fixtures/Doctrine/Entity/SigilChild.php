<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The subclass every seeded sigil is, so an owner of this class reaches
 * collections its parent declares.
 */
#[ORM\Entity]
class SigilChild extends Sigil
{
}
