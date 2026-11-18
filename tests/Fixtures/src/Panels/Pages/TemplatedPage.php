<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Sylament\Support\Enums\Width;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page rendering through a child template of the page chrome, which fills the header,
 * content and footer blocks from the page's own getter and a variable of its own; it mounts
 * with an argument of its own, sorts after the report page in navigation, and names its
 * content width as a class of its own.
 */
#[AsLiveComponent]
final class TemplatedPage extends Page
{
    protected static ?int $navigationSort = 2;

    protected ?string $template = '@Fixtures/panels/templated-page.html.twig';

    protected Width|string|null $maxContentWidth = 'reading-width';

    public string $edition = '';

    public function mount(string $edition = 'Spring'): void
    {
        $this->edition = $edition;
    }

    public function getEditionLabel(): string
    {
        return "The {$this->edition} edition";
    }
}
