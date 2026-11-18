<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use RuntimeException;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page that opts out of navigation and whose mount fails with no HTTP meaning.
 */
#[AsLiveComponent]
final class UnlistedFaultyPage extends Page
{
    protected static ?string $slug = 'faulty';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): never
    {
        throw new RuntimeException('The page could not load its figures.');
    }
}
