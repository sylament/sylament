<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\AmountType;
use Sylament\Schemas\Tests\Fixtures\Doctrine\SerialType;
use Sylament\Schemas\Tests\Fixtures\Doctrine\TokenType;

/**
 * A guid, a bigint and a decimal identifier, each under a type registered by a
 * name of its own.
 */
#[ORM\Entity]
class Voucher
{
    #[ORM\Id]
    #[ORM\Column(type: TokenType::NAME)]
    private string $token = '';

    #[ORM\Id]
    #[ORM\Column(type: SerialType::NAME)]
    private int|string $serial = 0;

    #[ORM\Id]
    #[ORM\Column(type: AmountType::NAME, precision: 10, scale: 2)]
    private string $amount = '';

    #[ORM\Column(length: 64)]
    private string $label = '';

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getSerial(): int|string
    {
        return $this->serial;
    }

    public function setSerial(int|string $serial): void
    {
        $this->serial = $serial;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
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
