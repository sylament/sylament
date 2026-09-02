<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages;

use BackedEnum;
use Closure;
use LogicException;
use ReflectionClass;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Concerns\HasSubNavigation;
use Sylament\Panels\Pages\Concerns\InteractsWithFormActions;
use Sylament\Panels\Pages\Concerns\InteractsWithHeaderActions;
use Sylament\Panels\Panel;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Width;
use Sylament\Support\Exceptions\Halt;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

use function Symfony\Component\String\u;

abstract class Page implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    /*
     * The alias stays ungrouped with the trait it renames: Rector splits a
     * grouped `use` and drops the adaptation block with it.
     */
    use HasActions {
        getAction as private getCachedOrDeclaredAction;
    }
    use HasSchemas;
    use HasSubNavigation;
    use InteractsWithFormActions;
    use InteractsWithHeaderActions;

    /**
     * The Twig component the controller wraps this page's document in. A page
     * names its own by redeclaring the static; the constant is what a subclass
     * points that static at.
     */
    public const string LAYOUT = 'SylamentPanels:Layout';

    protected static ?string $layout = null;

    protected static ?string $title = null;

    protected static ?string $slug = null;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|BackedEnum|null $activeNavigationIcon = null;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = null;

    protected static ?string $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = true;

    protected ?string $template = null;

    protected ?string $heading = null;

    protected ?string $subheading = null;

    protected Width|string|null $maxContentWidth = null;

    /**
     * The panel this page was opened in. A live request reaches ux's own route,
     * which carries no `_sylament_panel` default, so the prop restores it —
     * without it every URL a re-render mints, from the trail to a record link,
     * would answer for the *default* panel. Minted by the getter dehydration
     * reads rather than in a mount, because a page's mount signature is the
     * page's own.
     */
    #[LiveProp]
    public ?string $panelId = null;

    private ?RequestStack $requests = null;

    #[Required]
    public function setRequestStack(RequestStack $requests): void
    {
        $this->requests = $requests;
    }

    public function getPanelId(): ?string
    {
        return $this->panelId ??= $this->currentPanel?->get()?->getId();
    }

    /**
     * Above actions' re-resolution of a mounted action at 10: every URL minted
     * there has to answer for this panel rather than the default one.
     */
    #[PostHydrate(priority: 20)]
    public function stampPanelRequestAttribute(): void
    {
        if ($this->panelId === null) {
            return;
        }

        $this->requests?->getCurrentRequest()?->attributes->set('_sylament_panel', $this->panelId);
    }

    public function getPanel(): Panel
    {
        return $this->currentPanel?->get()
            ?? $this->panels?->getDefault()
            ?? throw new LogicException(sprintf('The page [%s] was built outside the container, so it can name no panel; register it as an autowired service.', static::class));
    }

    public function getLayout(): string
    {
        return static::$layout ?? self::LAYOUT;
    }

    /**
     * The variables this page hands its layout beyond the ones the controller
     * resolves for every page.
     *
     * @return array<string, mixed>
     */
    public function getLayoutData(): array
    {
        return [];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return $this->maxContentWidth;
    }

    /**
     * The classes this page's root element carries beyond the shared ones — the
     * scoping hook a stylesheet targets one page through.
     *
     * @return list<string>
     */
    public function getPageClasses(): array
    {
        return [];
    }

    /**
     * @return array<string, scalar>
     */
    public function getExtraBodyAttributes(): array
    {
        return [];
    }

    /**
     * The discovery list is empty rather than the default `schema`, which would
     * demand a `schema()` method on every dehydrate; a page names what it
     * declares. `content` stays out of it deliberately: the name resolves at
     * render, so an empty content schema costs a live request nothing.
     *
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema;
    }

    public function hasUnsavedDataChangesAlert(): bool
    {
        return false;
    }

    /**
     * The trait's own lookup first, then the two declared lists — a page's
     * header and form actions are built lazily, so an action reached by name
     * before either walk ran is what forces them.
     */
    public function getAction(string $name): ?Action
    {
        $action = $this->getCachedOrDeclaredAction($name);

        if ($action instanceof Action || ($this->cachedHeaderActions !== null && $this->cachedFormActions !== null)) {
            return $action;
        }

        $this->getCachedHeaderActions();
        $this->getCachedFormActions();

        return $this->getCachedOrDeclaredAction($name);
    }

    #[LiveListener('refresh-page')]
    public function refresh(): void
    {
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? static::getHumanizedClassBasename();
    }

    public function getHeading(): string|Markup|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Markup|null
    {
        return $this->subheading;
    }

    /**
     * A string key is the crumb's URL and renders as a link; an int key renders
     * as plain text.
     *
     * @return array<int|string, string|Markup>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTemplate(): string
    {
        return $this->template ?? '@SylamentPanels/partials/Page/index.html.twig';
    }

    public static function getSlug(): string
    {
        return static::$slug ?? u(new ReflectionClass(static::class)->getShortName())->kebab()->toString();
    }

    public static function getRoutePath(): string
    {
        return '/' . static::getSlug();
    }

    public static function getRouteName(Panel $panel): string
    {
        return sprintf('sylament.%s.pages.%s', $panel->getId(), str_replace('/', '.', static::getSlug()));
    }

    public static function getComponentName(): string
    {
        return str_replace('\\', ':', static::class);
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::$title ?? static::getHumanizedClassBasename();
    }

    public static function getNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$navigationIcon;
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$activeNavigationIcon ?? static::getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return static::$navigationSort;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::$shouldRegisterNavigation;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    /**
     * @return string|array<int|string, string>|null
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return null;
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(Panel $panel): array
    {
        $routeName = static::getRouteName($panel);

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->url(fn (UrlGeneratorInterface $urlGenerator): string => $urlGenerator->generate($routeName))
                ->isActiveWhen(fn (RequestStack $requestStack): bool => $requestStack->getCurrentRequest()?->attributes->get('_route') === $routeName),
        ];
    }

    protected static function getHumanizedClassBasename(): string
    {
        return u(new ReflectionClass(static::class)->getShortName())->kebab()->replace('-', ' ')->title(allWords: true)->toString();
    }

    /**
     * Filament's convention hook: `beforeSave`, `afterCreate`, … run when the
     * page declares a method of that name, and are silent otherwise.
     */
    protected function callHook(string $hook): void
    {
        if (! method_exists($this, $hook)) {
            return;
        }

        $this->{$hook}();
    }

    protected function halt(bool $shouldRollbackDatabaseTransaction = false): void
    {
        throw new Halt()->rollBackDatabaseTransaction($shouldRollbackDatabaseTransaction);
    }
}
