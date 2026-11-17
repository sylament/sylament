<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Actions\Doctrine\RegisterLineListener;

/**
 * A record keyed by two strings the application assigns, its line blank until written,
 * whose mapped entity listener fills a blank line; it has no lifecycle callback of its own.
 */
#[ORM\Entity]
#[ORM\EntityListeners([RegisterLineListener::class])]
class ListenedRegisterLine
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public string $register = '';

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public string $line = '';

    #[ORM\Column(length: 32)]
    public string $label = '';
}
