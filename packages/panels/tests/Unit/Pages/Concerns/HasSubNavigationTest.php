<?php

declare(strict_types=1);

use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\Page as ResourcePage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

final class SubNavigationAlphaPage extends Page
{
    protected static ?string $navigationLabel = 'Alpha';

    protected static ?int $navigationSort = 2;
}

final class SubNavigationBetaPage extends Page
{
    protected static ?string $navigationLabel = 'Beta';

    protected static ?int $navigationSort = 1;
}

final class SubNavigationUnregisteredPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
}

final class SubNavigationGroupedPage extends Page
{
    protected static ?string $navigationLabel = 'Grouped';

    protected static ?string $navigationGroup = 'System';
}

final class SubNavigationRecordPage extends ResourcePage
{
}

/**
 * A generator that answers the route name it was handed, so a generated item's URL
 * names the route the page resolved rather than a path the rig invented.
 */
function subNavigationUrlGenerator(): UrlGeneratorInterface
{
    return new class implements UrlGeneratorInterface {
        private RequestContext $context;

        public function __construct()
        {
            $this->context = new RequestContext();
        }

        public function setContext(RequestContext $context): void
        {
            $this->context = $context;
        }

        public function getContext(): RequestContext
        {
            return $this->context;
        }

        /**
         * @param array<string, mixed> $parameters
         */
        public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
        {
            return '/' . $name;
        }
    };
}

function subNavigationPanels(?SubNavigationPosition $position = null): PanelRegistry
{
    return new PanelRegistry([
        new class ($position) extends PanelProvider {
            public function __construct(private readonly ?SubNavigationPosition $position)
            {
            }

            public function panel(Panel $panel): Panel
            {
                return $panel
                    ->id('admin')
                    ->path('admin')
                    ->default()
                    ->subNavigationPosition($this->position);
            }
        },
    ]);
}

function wireSubNavigationServices(Page $page, ?SubNavigationPosition $position = null): Page
{
    $panels = subNavigationPanels($position);

    $page->setSubNavigationServices(new CurrentPanel(new RequestStack(), $panels), $panels, subNavigationUrlGenerator());

    return $page;
}

/**
 * @param array<NavigationItem|NavigationGroup> $subNavigation
 */
function subNavigationPage(array $subNavigation = []): Page
{
    $page = new class extends Page {
        /**
         * @var array<NavigationItem|NavigationGroup>
         */
        public array $subNavigation = [];

        /**
         * @return array<NavigationItem|NavigationGroup>
         */
        public function getSubNavigation(): array
        {
            return $this->subNavigation;
        }
    };

    $page->subNavigation = $subNavigation;

    return $page;
}

it('carries no sub-navigation by default', function (): void {
    $page = new class extends Page {};

    expect($page->getSubNavigation())->toBe([])
        ->and($page->getCachedSubNavigation())->toBe([])
        ->and($page->getSubNavigationParameters())->toBe([]);
});

it('answers the start position when it holds no panel services', function (): void {
    $page = new class extends Page {};

    expect($page->getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
});

it('reads the position off the panel when the page names none', function (): void {
    $page = wireSubNavigationServices(subNavigationPage(), SubNavigationPosition::Top);

    expect($page->getSubNavigationPosition())->toBe(SubNavigationPosition::Top);
});

it('falls back to the start position when the panel names none either', function (): void {
    $page = wireSubNavigationServices(subNavigationPage());

    expect($page->getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
});

it('lets the page static win over the panel knob', function (): void {
    $page = new class extends Page {
        protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;
    };

    wireSubNavigationServices($page, SubNavigationPosition::Top);

    expect($page->getSubNavigationPosition())->toBe(SubNavigationPosition::End);
});

it('wraps ungrouped items in one label-less group', function (): void {
    $page = subNavigationPage([
        NavigationItem::make('Second')->sort(2),
        NavigationItem::make('First')->sort(1),
    ]);

    $groups = $page->getCachedSubNavigation();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]->getLabel())->toBeNull()
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[0]->getItems()))->toBe(['First', 'Second']);
});

it('folds grouped items into label-keyed groups behind the ungrouped one', function (): void {
    $page = subNavigationPage([
        NavigationItem::make('Grouped')->group('Content'),
        NavigationItem::make('Loose'),
        NavigationGroup::make('System')->items([NavigationItem::make('Settings')]),
    ]);

    $groups = $page->getCachedSubNavigation();

    expect($groups)->toHaveCount(3)
        ->and($groups[0]->getLabel())->toBeNull()
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[0]->getItems()))->toBe(['Loose'])
        ->and($groups[1]->getLabel())->toBe('System')
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[1]->getItems()))->toBe(['Settings'])
        ->and($groups[2]->getLabel())->toBe('Content')
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[2]->getItems()))->toBe(['Grouped']);
});

it('folds a declared group carrying no label into the ungrouped one', function (): void {
    // Two label-less groups would draw as two unlabelled bands with nothing to
    // tell them apart; Filament's normalization ends with exactly one.
    $page = subNavigationPage([
        NavigationItem::make('Loose')->sort(2),
        NavigationGroup::make()->items([
            NavigationItem::make('Declared')->sort(1),
            NavigationItem::make('Concealed')->hidden(),
        ]),
        NavigationGroup::make('System')->items([NavigationItem::make('Settings')]),
    ]);

    $groups = $page->getCachedSubNavigation();

    expect($groups)->toHaveCount(2)
        ->and($groups[0]->getLabel())->toBeNull()
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[0]->getItems()))->toBe(['Declared', 'Loose'])
        ->and($groups[1]->getLabel())->toBe('System');
});

it('sorts the items inside a declared group', function (): void {
    $page = subNavigationPage([
        NavigationGroup::make('System')->items([
            NavigationItem::make('Late')->sort(9),
            NavigationItem::make('Early')->sort(1),
        ]),
        NavigationItem::make('Folded')->group('System')->sort(5),
    ]);

    $groups = $page->getCachedSubNavigation();

    expect($groups)->toHaveCount(1)
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[0]->getItems()))->toBe(['Early', 'Folded', 'Late']);
});

it('drops hidden items from both the loose list and a declared group', function (): void {
    $page = subNavigationPage([
        NavigationItem::make('Visible'),
        NavigationItem::make('Hidden')->hidden(),
        NavigationGroup::make('System')->items([
            NavigationItem::make('Shown'),
            NavigationItem::make('Concealed')->visible(false),
        ]),
    ]);

    $groups = $page->getCachedSubNavigation();

    expect(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[0]->getItems()))->toBe(['Visible'])
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $groups[1]->getItems()))->toBe(['Shown']);
});

it('hands back groups that are never collapsible', function (): void {
    $page = subNavigationPage([
        NavigationItem::make('Loose'),
        NavigationGroup::make('System')->collapsible()->items([NavigationItem::make('Settings')]),
    ]);

    $groups = $page->getCachedSubNavigation();

    expect($groups[0]->isCollapsible())->toBeFalse()
        ->and($groups[1]->isCollapsible())->toBeFalse()
        ->and($groups[1]->isCollapsed())->toBeFalse();
});

it('memoizes the normalized groups for the render', function (): void {
    $page = new class extends Page {
        /**
         * @var array<NavigationItem|NavigationGroup>
         */
        public array $subNavigation = [];

        /**
         * @return array<NavigationItem|NavigationGroup>
         */
        public function getSubNavigation(): array
        {
            return $this->subNavigation;
        }
    };

    $page->subNavigation = [NavigationItem::make('First')];

    $groups = $page->getCachedSubNavigation();

    $page->subNavigation = [NavigationItem::make('Second')];

    expect($page->getCachedSubNavigation())->toBe($groups);
});

it('builds one item per plain page, active on the page it is asked from', function (): void {
    $page = wireSubNavigationServices(new SubNavigationAlphaPage());

    $items = $page->generateNavigationItems([SubNavigationAlphaPage::class, SubNavigationBetaPage::class]);

    expect($items)->toHaveCount(2)
        ->and($items[0]->getLabel())->toBe('Alpha')
        ->and($items[0]->getSort())->toBe(2)
        ->and($items[0]->getUrl())->toBe('/sylament.admin.pages.sub-navigation-alpha-page')
        ->and($items[0]->isActive())->toBeTrue()
        ->and($items[1]->getLabel())->toBe('Beta')
        ->and($items[1]->getUrl())->toBe('/sylament.admin.pages.sub-navigation-beta-page')
        ->and($items[1]->isActive())->toBeFalse();
});

it('carries a page navigation group onto the item it builds', function (): void {
    $page = wireSubNavigationServices(new SubNavigationAlphaPage());

    $items = $page->generateNavigationItems([SubNavigationGroupedPage::class]);
    $groups = subNavigationPage($items)->getCachedSubNavigation();

    expect($items[0]->getGroup())->toBe('System')
        ->and($groups)->toHaveCount(1)
        ->and($groups[0]->getLabel())->toBe('System');
});

it('skips a page that opts out of navigation registration', function (): void {
    $page = wireSubNavigationServices(new SubNavigationAlphaPage());

    expect(SubNavigationUnregisteredPage::shouldRegisterNavigation())->toBeFalse()
        ->and(SubNavigationAlphaPage::shouldRegisterNavigation())->toBeTrue()
        ->and($page->generateNavigationItems([SubNavigationUnregisteredPage::class]))->toBe([]);
});

it('refuses a resource page until the record pages land', function (): void {
    $page = wireSubNavigationServices(new SubNavigationAlphaPage());

    $page->generateNavigationItems([SubNavigationRecordPage::class]);
})->throws(LogicException::class, 'resource sub-navigation lands with the record pages');

it('refuses to build a plain page URL outside any panel', function (): void {
    new SubNavigationAlphaPage()->generateNavigationItems([SubNavigationBetaPage::class]);
})->throws(LogicException::class, 'builds sub-navigation outside any panel');
