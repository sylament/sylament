<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Actions\Action;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page no panel lists, whose subheading names the panel it answers for, and whose header
 * declaration looks up by name an action nothing declares, as a declaration that defers to
 * another action may.
 */
#[AsLiveComponent]
final class OrphanPage extends Page
{
    public function getSubheading(): string
    {
        return "Opened in the {$this->getPanel()->getId()} panel";
    }

    public function getHeaderActions(): array
    {
        return [Action::make('publish')->hidden($this->getAction('publishDraft') instanceof Action)];
    }
}
