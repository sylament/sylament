<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page whose navigation entry (label, group and badge, which are static getters) is catalogue
 * messages, and whose breadcrumbs, a linked crumb and a plain one, it translates itself.
 */
#[AsLiveComponent]
final class TranslatedDeskPage extends Page
{
    public static function getNavigationLabel(): TranslatableInterface
    {
        return new TranslatableMessage('page.label', [], 'fixtures-panels');
    }

    public static function getNavigationGroup(): TranslatableInterface
    {
        return new TranslatableMessage('page.group', [], 'fixtures-panels');
    }

    public static function getNavigationBadge(): TranslatableInterface
    {
        return new TranslatableMessage('page.badge', [], 'fixtures-panels');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/translated/translated-desk-page' => new TranslatableMessage('crumb.home', [], 'fixtures-panels')->trans($this->getTranslator()),
            new TranslatableMessage('crumb.here', ['page' => 'Desk'], 'fixtures-panels')->trans($this->getTranslator()),
        ];
    }
}
