<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single identifier that is not called `id` and has no `id` property beside
 * it — the shape a mounted action has always minted nothing for.
 */
#[ORM\Entity]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
