<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Note;

/**
 * The titleless resource: it names a record class and nothing to read a title
 * off, so global search has no attribute to fall back on and skips it. It is
 * deliberately left out of `TestPanelProvider` — registering it would change
 * the navigation and route expectations every other fixture pins — and
 * `ResourceRegistry` still resolves it, since the fixture directory is
 * PSR-4-loaded and autoconfigured.
 */
final class UntitledResource extends Resource
{
    protected static ?string $recordClass = Note::class;
}
