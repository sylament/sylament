<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A clipping of a {@see Portfolio} whose identifier the database generates, so
 * a new item has no key until the flush.
 */
#[ORM\Entity]
class PortfolioClipping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'clippings')]
    private ?Portfolio $portfolio = null;

    #[ORM\Column(length: 255)]
    private string $text = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): static
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = (string) $text;

        return $this;
    }
}
