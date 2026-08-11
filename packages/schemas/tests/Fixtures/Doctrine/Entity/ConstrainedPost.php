<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A record carrying its own constraint metadata: a cascading association, and a
 * class-level callback whose sentinel titles produce a violation at a mapped
 * property, at an unmapped one, and at the class itself — the three paths the
 * `getData()` tail has to place.
 */
#[ORM\Entity]
class ConstrainedPost
{
    public const string REJECTED_TITLE = 'reject-title';

    public const string REJECTED_SLUG = 'reject-slug';

    public const string REJECTED_POST = 'reject-post';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title = '';

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[Assert\Valid]
    private ?ConstrainedAuthor $author = null;

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

    public function getAuthor(): ?ConstrainedAuthor
    {
        return $this->author;
    }

    public function setAuthor(?ConstrainedAuthor $author): void
    {
        $this->author = $author;
    }

    #[Assert\Callback]
    public function reviewTitle(ExecutionContextInterface $context): void
    {
        [$message, $path] = match ($this->title) {
            self::REJECTED_TITLE => ['That title is taken.', 'title'],
            self::REJECTED_SLUG => ['That slug is taken.', 'slug'],
            self::REJECTED_POST => ['That post is not publishable.', null],
            default => [null, null],
        };

        if ($message === null) {
            return;
        }

        $builder = $context->buildViolation($message);

        if ($path !== null) {
            $builder->atPath($path);
        }

        $builder->addViolation();
    }
}
