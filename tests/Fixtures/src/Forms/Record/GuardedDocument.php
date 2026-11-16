<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms\Record;

/**
 * A record holding one stored file path, or none: built without one, its typed property
 * stays uninitialised.
 */
final class GuardedDocument
{
    public string $document;

    public function __construct(?string $document = null)
    {
        if ($document !== null) {
            $this->document = $document;
        }
    }
}
