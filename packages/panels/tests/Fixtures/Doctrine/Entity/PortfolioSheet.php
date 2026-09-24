<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A sheet keyed by its {@see Portfolio} and a code the form types, so a new
 * item is keyed only once its owner is.
 */
#[ORM\Entity]
class PortfolioSheet
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'sheets')]
    private ?Portfolio $portfolio = null;

    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private string $caption = '';

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): static
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = (string) $caption;

        return $this;
    }
}
