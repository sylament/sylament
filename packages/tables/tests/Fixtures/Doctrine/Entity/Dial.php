<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The single-table leaf a {@see Depot} collects.
 */
#[ORM\Entity]
class Dial extends Gauge
{
}
