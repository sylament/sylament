<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

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
 * A host whose writable `activeTab` property opens one tab of a persisted Tabs built from a
 * closure: `open`, unlabelled and named by `generateTabLabel()`, with a lazy-loaded badge,
 * and `closed`, labelled `Archived`, holding a Text keyed `delivered`.
 */
#[AsLiveComponent(name: 'Schemas:PropertyDrivenTabsHost', template: '@Fixtures/schemas/PropertyDrivenTabsHost.html.twig')]
class PropertyDrivenTabsHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public string $activeTab = 'open';

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Orders')
                ->liveComponentProperty('activeTab')
                ->persistTab()
                ->tabs(static fn (): array => [
                    'open' => Tab::make()->badge('7')->lazyLoadedBadge()->components([Text::make('Orders awaiting dispatch')]),
                    'closed' => Tab::make('Archived')->components([Text::make('Orders delivered')->key('delivered')]),
                ]),
        ]);
    }

    public function generateTabLabel(string $key): string
    {
        return ucfirst($key);
    }
}
