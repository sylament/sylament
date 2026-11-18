<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page that declares nothing, so everything it shows is the base's default.
 */
#[AsLiveComponent]
final class NotesPage extends Page
{
}
