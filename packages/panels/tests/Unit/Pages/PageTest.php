<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Width;
use Sylament\Support\Exceptions\Halt;
use Symfony\UX\LiveComponent\Attribute\LiveListener;

final class LayoutNamingPage extends Page
{
    protected static ?string $layout = 'Custom:Layout';
}

final class WidthNamingPage extends Page
{
    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;
}

/**
 * Exposes the base's halt, which is protected and has no other route into a Unit
 * test.
 */
final class HaltingProbePage extends Page
{
    public function stop(bool $shouldRollbackDatabaseTransaction = false): void
    {
        $this->halt($shouldRollbackDatabaseTransaction);
    }
}

/**
 * Declares its one action in the form row alone, so only the form walk can
 * resolve it by name.
 */
final class FormRowActionPage extends Page
{
    public int $reads = 0;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        $this->reads++;

        return [Action::make('ping')];
    }
}

it('exposes title, heading fallback, and navigation statics', function (): void {
    $page = new class extends Page {
        protected static ?string $title = 'Dashboard';

        protected static ?string $navigationLabel = 'Home';

        protected static ?int $navigationSort = 2;
    };

    expect($page->getTitle())->toBe('Dashboard')
        ->and($page->getHeading())->toBe('Dashboard')
        ->and($page->getSubheading())->toBeNull()
        ->and($page::getNavigationLabel())->toBe('Home')
        ->and($page::getNavigationSort())->toBe(2)
        ->and($page::getNavigationIcon())->toBeNull()
        ->and($page::getNavigationGroup())->toBeNull();
});

it('defaults the optional navigation statics to null', function (): void {
    $page = new class extends Page {};

    expect($page->getSubheading())->toBeNull()
        ->and($page::getNavigationIcon())->toBeNull()
        ->and($page::getNavigationGroup())->toBeNull()
        ->and($page::getNavigationSort())->toBeNull();
});

it('carries no breadcrumb trail by default', function (): void {
    $page = new class extends Page {};

    expect($page->getBreadcrumbs())->toBe([])
        ->and(new SettingsPage()->getBreadcrumbs())->toBe([])
        ->and(new Dashboard()->getBreadcrumbs())->toBe([]);
});

it('derives slug, route path, route name and component name from the class', function (): void {
    $page = new class extends Page {};

    expect($page::getSlug())->not->toBe('')
        ->and($page::getRoutePath())->toBe('/' . $page::getSlug());

    expect(SettingsPage::getSlug())->toBe('settings-page')
        ->and(SettingsPage::getRoutePath())->toBe('/settings-page')
        ->and(SettingsPage::getRouteName(Panel::make()->id('admin')))->toBe('sylament.admin.pages.settings-page')
        ->and(SettingsPage::getComponentName())->toBe('Sylament:Panels:Tests:Fixtures:Pages:SettingsPage');
});

it('falls back to the humanized class basename for title and navigation label', function (): void {
    expect(SettingsPage::getNavigationLabel())->toBe('Settings');

    $page = new SettingsPage();

    expect($page->getTitle())->toBe('Settings Page')
        ->and($page->getHeading())->toBe('Settings Page');
});

it('builds one navigation item wired to the page route', function (): void {
    $panel = Panel::make()->id('admin');
    $items = SettingsPage::getNavigationItems($panel);

    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(NavigationItem::class)
        ->and($items[0]->getLabel())->toBe('Settings')
        ->and($items[0]->getGroup())->toBe('Content');
});

it('defaults the template to the shipped page chrome template', function (): void {
    $page = new SettingsPage();

    expect($page->getTemplate())->toBe('@SylamentPanels/partials/Page/index.html.twig');
});

it('is an actions host and a schemas host with an empty discovery list', function (): void {
    $page = new class extends Page {};

    // The discovery list is the load-bearing half: the trait's default is
    // `['schema']`, and a page that answered it would force a `schema()` method
    // on every dehydrate.
    expect($page)->toBeInstanceOf(InteractsWithActions::class)
        ->and($page)->toBeInstanceOf(InteractsWithSchemas::class)
        ->and($page->schemas())->toBe([]);
});

it('hands the content schema back untouched', function (): void {
    $page = new class extends Page {};
    $schema = Schema::make();

    expect($page->content($schema))->toBe($schema);
});

it('names the shipped layout, and lets a page name its own', function (): void {
    $page = new class extends Page {};

    expect($page->getLayout())->toBe('SylamentPanels:Layout')
        ->and(Page::LAYOUT)->toBe('SylamentPanels:Layout')
        ->and(new LayoutNamingPage()->getLayout())->toBe('Custom:Layout')
        ->and($page->getLayoutData())->toBe([]);
});

it('defaults the width, the page classes, the body attributes and the alert to nothing', function (): void {
    $page = new class extends Page {};

    expect($page->getMaxContentWidth())->toBeNull()
        ->and($page->getPageClasses())->toBe([])
        ->and($page->getExtraBodyAttributes())->toBe([])
        ->and($page->hasUnsavedDataChangesAlert())->toBeFalse();
});

it('lets a page name a width of its own', function (): void {
    expect(new WidthNamingPage()->getMaxContentWidth())->toBe(Width::FourExtraLarge);
});

it('aborts a hook ladder through halt, carrying the rollback flag', function (): void {
    $page = new HaltingProbePage();

    expect(fn (): mixed => $page->stop())->toThrow(Halt::class);

    try {
        $page->stop(true);
    } catch (Halt $halt) {
        expect($halt->shouldRollbackDatabaseTransaction())->toBeTrue();
    }

    try {
        $page->stop();
    } catch (Halt $halt) {
        expect($halt->shouldRollbackDatabaseTransaction())->toBeFalse();
    }
});

it('answers a client refresh as a listener of its own', function (): void {
    $attributes = new ReflectionMethod(Page::class, 'refresh')->getAttributes(LiveListener::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->getArguments())->toBe(['refresh-page']);
});

it('resolves an action declared in the form row by name', function (): void {
    $page = new FormRowActionPage();

    expect($page->getAction('ping'))->toBeInstanceOf(Action::class)
        ->and($page->getAction('ping')?->getName())->toBe('ping')
        ->and($page->getAction('nothing'))->toBeNull()
        ->and($page->reads)->toBe(1);
});

it('fails fast when a page built outside the container is asked for its panel', function (): void {
    new class extends Page {}->getPanel();
})->throws(LogicException::class, 'register it as an autowired service');
