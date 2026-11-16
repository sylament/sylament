<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use Sylament\Tests\Fixtures\Entity\Invoice;

/**
 * A record graph a dotted path walks: a nullable nested object that starts null, a non-nullable one never assigned,
 * a nested class whose constructor requires an argument, and an array column.
 */
final class Manuscript
{
    private ?string $title = null;

    private ?ManuscriptWriter $writer = null;

    private ManuscriptWriter $editor;

    private ?Invoice $invoice = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $meta = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getWriter(): ?ManuscriptWriter
    {
        return $this->writer;
    }

    public function setWriter(?ManuscriptWriter $writer): void
    {
        $this->writer = $writer;
    }

    public function getEditor(): ManuscriptWriter
    {
        return $this->editor;
    }

    public function setEditor(ManuscriptWriter $editor): void
    {
        $this->editor = $editor;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }
}
