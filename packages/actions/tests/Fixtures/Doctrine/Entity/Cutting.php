<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A record whose own class-level constraint refuses a wilted cutting, and a
 * cutting whose title opens with markup, without naming a field, so the
 * violation lands on the mounted schema's own state path rather than under any
 * field. The second message interpolates the title, so the outlet has markup
 * to escape.
 */
#[ORM\Entity]
#[Assert\Callback('refuseWilted')]
class Cutting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function refuseWilted(ExecutionContextInterface $context): void
    {
        if (str_starts_with($this->title, '<')) {
            $context->buildViolation('The cutting {{ title }} is refused as a whole.')->setParameter('{{ title }}', $this->title)->addViolation();
        }

        if ($this->title === 'wilted') {
            $context->buildViolation('This cutting is refused as a whole.')->addViolation();
        }
    }
}
