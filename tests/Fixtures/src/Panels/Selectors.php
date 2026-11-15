<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

/**
 * The panels area's render selectors.
 */
final class Selectors
{
    public const string DOCUMENT_TITLE = 'head > title';

    public static function field(string $name): string
    {
        return "input[name=\"{$name}\"]";
    }
}
