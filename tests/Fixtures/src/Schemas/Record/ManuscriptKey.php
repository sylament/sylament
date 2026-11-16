<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A record whose constructor sets properties no setter can change, in each shape the leaf rule reads: `$held` public and
 * readonly over whatever value a case gives it, `$code` private behind a getter, `$badge` behind a getter and never
 * given a value, `$secret` private and readonly with no getter. `$title` is the writable control, counting its writes.
 */
final class ManuscriptKey
{
    // @phpstan-ignore property.uninitializedReadonly, property.onlyRead (never given a value on purpose: the leaf rule's uninitialized arm)
    private readonly string $badge;

    private ?string $title = null;

    private int $titleWrites = 0;

    public function __construct(
        public readonly mixed $held = null,
        private readonly string $code = 'MS-7',
        private readonly string $secret = 'quill',
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getBadge(): string
    {
        return $this->badge;
    }

    public function matchesSecret(string $candidate): bool
    {
        return $this->secret === $candidate;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
        $this->titleWrites++;
    }

    public function getTitleWrites(): int
    {
        return $this->titleWrites;
    }
}
