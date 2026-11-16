<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CaselessType;

/**
 * A record keyed by a string its column stores lower-cased, with a to-one onto another
 * it cascades a persist to.
 */
#[ORM\Entity]
class CaselessHandle
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: CaselessType::NAME, length: 64)]
        public string $handle,
        #[ORM\ManyToOne(cascade: ['persist'])]
        #[ORM\JoinColumn(referencedColumnName: 'handle')]
        public ?self $alias = null,
    ) {
    }
}
