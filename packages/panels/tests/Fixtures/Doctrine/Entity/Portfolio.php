<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A generated-id record whose form saves three relationships at once: a
 * collection of {@see PortfolioSheet} keyed by the portfolio and a code the
 * form types, a collection of {@see PortfolioClipping} the database numbers,
 * and one {@see PortfolioSeal} with an assigned key of its own. The title
 * `refuse` fails its own check, so a save can be refused for a reason that has
 * nothing to do with a key.
 */
#[ORM\Entity]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, PortfolioSheet>
     */
    #[ORM\OneToMany(targetEntity: PortfolioSheet::class, mappedBy: 'portfolio', cascade: ['persist'], orphanRemoval: true)]
    private Collection $sheets;

    /**
     * @var Collection<int, PortfolioClipping>
     */
    #[ORM\OneToMany(targetEntity: PortfolioClipping::class, mappedBy: 'portfolio', cascade: ['persist'], orphanRemoval: true)]
    private Collection $clippings;

    #[ORM\OneToOne(targetEntity: PortfolioSeal::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'seal_serial', referencedColumnName: 'serial')]
    #[Assert\Valid]
    private ?PortfolioSeal $seal = null;

    public function __construct()
    {
        $this->sheets = new ArrayCollection();
        $this->clippings = new ArrayCollection();
    }

    #[Assert\Callback]
    public function refuseTitle(ExecutionContextInterface $context): void
    {
        if ($this->title === 'refuse') {
            $context->buildViolation('This portfolio title is refused.')->atPath('title')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = (string) $title;

        return $this;
    }

    /**
     * @return Collection<int, PortfolioSheet>
     */
    public function getSheets(): Collection
    {
        return $this->sheets;
    }

    public function addSheet(PortfolioSheet $sheet): static
    {
        if (! $this->sheets->contains($sheet)) {
            $this->sheets->add($sheet);
        }

        $sheet->setPortfolio($this);

        return $this;
    }

    public function removeSheet(PortfolioSheet $sheet): static
    {
        $this->sheets->removeElement($sheet);

        return $this;
    }

    /**
     * @return Collection<int, PortfolioClipping>
     */
    public function getClippings(): Collection
    {
        return $this->clippings;
    }

    public function addClipping(PortfolioClipping $clipping): static
    {
        if (! $this->clippings->contains($clipping)) {
            $this->clippings->add($clipping);
        }

        $clipping->setPortfolio($this);

        return $this;
    }

    public function removeClipping(PortfolioClipping $clipping): static
    {
        $this->clippings->removeElement($clipping);

        return $this;
    }

    public function getSeal(): ?PortfolioSeal
    {
        return $this->seal;
    }

    public function setSeal(?PortfolioSeal $seal): static
    {
        $this->seal = $seal;

        return $this;
    }
}
