<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Pages\Page;
use Sylament\Support\Enums\Width;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The width declared as a token *string* rather than the enum case — the arm
 * `PageController::resolveMaxContentWidthClass()` coerces back to a `Width`.
 */
#[AsLiveComponent]
final class TokenWidthPage extends Page
{
    protected static ?string $slug = 'token-width';

    protected Width|string|null $maxContentWidth = '4xl';
}
