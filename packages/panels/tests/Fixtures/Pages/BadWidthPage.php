<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Registered on no panel, exactly as `BadLayoutPage` is: the controller reaches
 * this one through a hand-built request alone.
 */
#[AsLiveComponent]
final class BadWidthPage extends Page
{
    /**
     * @return array<string, mixed>
     */
    public function getLayoutData(): array
    {
        return ['maxContentWidth' => 42];
    }
}
