<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use Sylament\Tests\Fixtures\Entity\Invoice;

/**
 * A record whose declared types are the subject: the four getter and setter pairings, whose last two disagree on null
 * (`getWriter()` vivifies what its nullable setter clears), native scalars, a union, an array and a nullable object.
 */
final class ManuscriptSignatures
{
    public int $pages = 0;

    public bool $published = false;

    public int|string $code = 0;

    /**
     * @var array<string, mixed>
     */
    public array $notes = [];

    public ?Invoice $invoice = null;

    private string $name = '';

    private ?string $bio = null;

    private ?ManuscriptWriter $writer = null;

    private ?ManuscriptWriter $reviewer = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function getWriter(): ManuscriptWriter
    {
        return $this->writer ??= new ManuscriptWriter();
    }

    public function setWriter(?ManuscriptWriter $writer): void
    {
        $this->writer = $writer;
    }

    public function getReviewer(): ?ManuscriptWriter
    {
        return $this->reviewer;
    }

    public function setReviewer(ManuscriptWriter $reviewer): void
    {
        $this->reviewer = $reviewer;
    }
}
