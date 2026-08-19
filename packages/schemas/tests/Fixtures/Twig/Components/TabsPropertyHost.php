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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host driving its tabs from a writable live property — the `liveComponentProperty()`
 * arm — and supplying the optional `generateTabLabel()` hook the Tabs partial probes for.
 */
#[AsLiveComponent(name: 'TabsPropertyHost', template: '@SchemasFixtures/components/TabsPropertyHost.html.twig')]
class TabsPropertyHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public string $activeTab = 'all';

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Articles')
                ->liveComponentProperty('activeTab')
                ->tabs([
                    'all' => Tab::make()->badge('7')->deferBadge()->components([Text::make('every article')]),
                    'published' => Tab::make()->components([Text::make('published only')]),
                ]),
        ]);
    }

    public function generateTabLabel(string $key): string
    {
        return ucfirst($key);
    }
}
