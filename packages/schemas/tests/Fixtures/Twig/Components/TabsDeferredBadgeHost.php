<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host carrying one client-driven Tabs whose first tab defers its badge — the
 * `getDeferredTabBadges()` seam the tabs controller calls through
 * `callSchemaComponentMethod()`.
 */
#[AsLiveComponent(name: 'TabsDeferredBadgeHost', template: '@SchemasFixtures/components/TabsDeferredBadgeHost.html.twig')]
class TabsDeferredBadgeHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('T')->tabs([
                Tab::make('A')->badge('7')->badgeColor('danger')->deferBadge()->components([Text::make('a')]),
                Tab::make('B')->components([Text::make('b')]),
            ]),
        ]);
    }
}
