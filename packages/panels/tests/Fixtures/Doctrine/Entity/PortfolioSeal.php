<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * The {@see Portfolio}'s one seal: a single identifier the form assigns,
 * reached through a cascading to-one. Its title fails a length rule past eight
 * characters, fails a field-level check as `refuse` and the whole record as
 * `whole`, so a violation can land on a field of the related record or on the
 * record itself.
 */
#[ORM\Entity]
class PortfolioSeal
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $serial = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 8, maxMessage: 'This seal title is too long.')]
    private string $title = '';

    #[Assert\Callback]
    public function refuseTitle(ExecutionContextInterface $context): void
    {
        if ($this->title === 'refuse') {
            $context->buildViolation('This seal title is refused.')->atPath('title')->addViolation();
        }

        if ($this->title === 'whole') {
            $context->buildViolation('This seal is refused as a whole.')->addViolation();
        }
    }

    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function setSerial(?string $serial): static
    {
        $this->serial = $serial;

        return $this;
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
}
