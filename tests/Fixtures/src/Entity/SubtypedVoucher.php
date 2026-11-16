<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherBigIntType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherBooleanType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherDecimalType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherGuidType;

/**
 * A record keyed by a guid, a bigint, a decimal and a boolean, each through a type that
 * extends DBAL's own under a name of its own. The bigint half is untyped beyond
 * `int|string`, so a new record can hold the spelling a form typed.
 */
#[ORM\Entity]
class SubtypedVoucher
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: VoucherGuidType::NAME, length: 36)]
        public string $token,
        #[ORM\Id]
        #[ORM\Column(type: VoucherBigIntType::NAME)]
        public int|string $serial,
        #[ORM\Id]
        #[ORM\Column(type: VoucherDecimalType::NAME, precision: 10, scale: 2)]
        public string $amount,
        #[ORM\Id]
        #[ORM\Column(type: VoucherBooleanType::NAME)]
        public bool $redeemed,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
