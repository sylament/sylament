<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\SimplePage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A simple page that hands the document's `<body>` an attribute of its own.
 */
#[AsLiveComponent]
final class AttributedSignInPage extends SimplePage
{
    protected static ?string $slug = 'sign-in';

    public function getExtraBodyAttributes(): array
    {
        return ['data-screen' => 'sign-in'];
    }
}
