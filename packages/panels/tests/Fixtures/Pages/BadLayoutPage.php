<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Registered on no panel: the page locator holds every tagged page, so the
 * controller reaches this one through a hand-built request alone.
 */
#[AsLiveComponent]
final class BadLayoutPage extends Page
{
    public function getLayout(): string
    {
        return 'Nope';
    }
}
