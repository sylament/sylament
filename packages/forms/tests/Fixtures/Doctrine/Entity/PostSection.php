<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
class PostSection
{
    /**
     * The heading a child rejects itself for, so a record-validating test can
     * fail one item of a collection while every other test's headings stay
     * acceptable.
     */
    public const string REJECTED_HEADING = 'reject-heading';

    /**
     * The heading a child rejects itself for as a whole, so a test can raise a
     * class-level violation on one item of a collection.
     */
    public const string REJECTED_SECTION = 'reject-section';

    /**
     * The heading a child rejects its body for, so a test can raise a violation
     * on a property the item schema declares no field for.
     */
    public const string REJECTED_BODY = 'reject-body';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $heading = '';

    #[ORM\Column(nullable: true)]
    private ?string $body = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function setHeading(string $heading): void
    {
        $this->heading = $heading;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
    }

    #[Assert\Callback]
    public function reviewHeading(ExecutionContextInterface $context): void
    {
        if ($this->heading === self::REJECTED_HEADING) {
            $context->buildViolation('That heading is taken.')->atPath('heading')->addViolation();

            return;
        }

        if ($this->heading === self::REJECTED_SECTION) {
            $context->buildViolation('That section is not publishable.')->addViolation();

            return;
        }

        if ($this->heading === self::REJECTED_BODY) {
            $context->buildViolation('That body is too short.')->atPath('body')->addViolation();
        }
    }
}
