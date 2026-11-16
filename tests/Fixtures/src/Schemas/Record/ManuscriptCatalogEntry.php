<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A record whose sides disagree on its one property's type: the promoted constructor and its docblock say a string,
 * the setter a nullable int.
 */
final class ManuscriptCatalogEntry
{
    /**
     * @param non-empty-string $code
     */
    public function __construct(
        private string $code = 'A1',
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(?int $code): void
    {
        $this->code = $code === null ? 'A1' : (string) $code;
    }
}
