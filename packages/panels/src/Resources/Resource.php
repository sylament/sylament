<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources;

use BackedEnum;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Stringable;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Resources\RelationManagers\RelationManagerConfiguration;
use Sylament\Panels\Resources\Resource\Concerns\BelongsToParent;
use Sylament\Panels\Resources\Resource\Concerns\HasBreadcrumbs;
use Sylament\Panels\Resources\Resource\Concerns\HasGlobalSearch;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Tables\Table;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\get_record_label;
use function Sylament\Support\plural;
use function Symfony\Component\String\u;

abstract class Resource
{
    use BelongsToParent;
    use HasBreadcrumbs;
    use HasGlobalSearch;

    /**
     * @var class-string|null
     */
    protected static ?string $recordClass = null;

    protected static ?string $slug = null;

    protected static ?string $recordLabel = null;

    protected static ?string $pluralRecordLabel = null;

    protected static ?string $recordTitleAttribute = null;

    protected static ?string $recordRouteKeyName = null;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|BackedEnum|null $activeNavigationIcon = null;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = null;

    protected static ?string $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?SubNavigationPosition $subNavigationPosition = null;

    private ?EntityManagerInterface $entityManager = null;

    private ?UrlGeneratorInterface $urlGenerator = null;

    private ?CurrentPanel $currentPanel = null;

    private ?PanelRegistry $panels = null;

    private ?PropertyAccessor $propertyAccessor = null;

    private ?AssociationReader $associationReader = null;

    private ?ResourceRegistry $resources = null;

    #[Required]
    public function setResourceServices(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CurrentPanel $currentPanel,
        PanelRegistry $panels,
        PropertyAccessor $propertyAccessor,
        AssociationReader $associationReader,
        ResourceRegistry $resources,
    ): void {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->currentPanel = $currentPanel;
        $this->panels = $panels;
        $this->propertyAccessor = $propertyAccessor;
        $this->associationReader = $associationReader;
        $this->resources = $resources;
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    /**
     * @return array<string, PageRegistration>
     */
    public function getPages(): array
    {
        return [];
    }

    /**
     * The relation managers a record page of this resource mounts, keyed by the
     * identity its tab is activated under.
     *
     * @return array<class-string<RelationManager>|RelationGroup|RelationManagerConfiguration>
     */
    public function getRelations(): array
    {
        return [];
    }

    public function hasPage(string $page): bool
    {
        return array_key_exists($page, $this->getPages());
    }

    /**
     * @return class-string
     */
    public function getRecordClass(): string
    {
        return static::$recordClass ?? throw new LogicException(sprintf('The resource [%s] must declare its record class — set the $recordClass static property.', static::class));
    }

    public function getSlug(): string
    {
        $slug = static::$slug;

        if (filled($slug)) {
            return $slug;
        }

        $basename = u(static::class)->afterLast('\\');

        if ($basename->endsWith('Resource')) {
            $basename = $basename->beforeLast('Resource');
        }

        return u(plural($basename->toString()))->kebab()->toString();
    }

    public function getRecordLabel(): string
    {
        $label = static::$recordLabel;

        if (filled($label)) {
            return $label;
        }

        return get_record_label($this->getRecordClass());
    }

    public function getPluralRecordLabel(): string
    {
        $label = static::$pluralRecordLabel;

        if (filled($label)) {
            return $label;
        }

        return plural($this->getRecordLabel());
    }

    public function getTitleCaseRecordLabel(): string
    {
        return u($this->getRecordLabel())->title(allWords: true)->toString();
    }

    public function getTitleCasePluralRecordLabel(): string
    {
        return u($this->getPluralRecordLabel())->title(allWords: true)->toString();
    }

    public function getRecordTitleAttribute(): ?string
    {
        return static::$recordTitleAttribute;
    }

    public function hasRecordTitle(): bool
    {
        return $this->getRecordTitleAttribute() !== null;
    }

    public function getRecordTitle(?object $record): string
    {
        $attribute = $this->getRecordTitleAttribute();

        if ($record === null || $attribute === null) {
            return $this->getRecordLabel();
        }

        try {
            $title = $this->getPropertyAccessor()->getFromRecord($record, $attribute);
        } catch (AccessException) {
            return $this->getRecordLabel();
        }

        if ($title instanceof Stringable) {
            $title = (string) $title;
        }

        $title = is_scalar($title) ? (string) $title : null;

        if (filled($title)) {
            return $title;
        }

        return $this->getRecordLabel();
    }

    public function getRecordQuery(): QueryBuilder
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('record')
            ->from($this->getRecordClass(), 'record');
    }

    /**
     * The record one route key names, or null. With no `$recordRouteKeyName`, a
     * key no record of this class could mint names none, and is answered
     * without a statement. The modifier receives the record query and returns
     * the one to key against — a different builder, such as a parent-scoped
     * one, is what narrows a nested resource's binding.
     */
    public function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?object
    {
        $query = clone $this->getRecordQuery();

        if ($modifyQuery instanceof Closure) {
            $modifiedQuery = $modifyQuery($query);

            if ($modifiedQuery !== null && ! $modifiedQuery instanceof QueryBuilder) {
                throw new LogicException(sprintf('The query modifier passed to [%s::resolveRecordRouteBinding()] must return a query builder or null; it returned %s.', static::class, get_debug_type($modifiedQuery)));
            }

            $query = $modifiedQuery ?? $query;
        }

        $rootAlias = $query->getRootAliases()[0]
            ?? throw new LogicException(sprintf('The record query of resource [%s] names no root alias to resolve a record against.', static::class));

        $keyName = $this->getRecordRouteKeyName();

        if ($keyName === null) {
            $predicate = IdentifierConstraint::equals($query, $rootAlias, $this->getRecordClass(), (string) $key, 'sylament_record_key');

            if ($predicate === null) {
                return null;
            }

            $query->andWhere($predicate);
        } else {
            $query
                ->andWhere(sprintf('%s.%s = :sylament_record_key', $rootAlias, $keyName))
                ->setParameter('sylament_record_key', $key);
        }

        $record = $query->getQuery()->getOneOrNullResult();

        return is_object($record) ? $record : null;
    }

    public function getRecordRouteKeyName(): ?string
    {
        return static::$recordRouteKeyName;
    }

    public function getRecordRouteKey(object $record): string
    {
        $recordClass = $this->getRecordClass();

        if (! $record instanceof $recordClass) {
            throw new LogicException(sprintf('Resource [%s] speaks for [%s]; it cannot key a [%s].', static::class, $recordClass, $record::class));
        }

        $keyName = $this->getRecordRouteKeyName();

        if ($keyName === null) {
            return RecordKey::of($this->getEntityManager(), $record, $recordClass);
        }

        $key = $this->getPropertyAccessor()->getFromRecord($record, $keyName);

        if ($key instanceof Stringable) {
            $key = (string) $key;
        }

        if (blank($key) || ! is_scalar($key)) {
            throw new LogicException(sprintf('The route key of [%s] is not a printable value.', $record::class));
        }

        return (string) $key;
    }

    public function getRouteBaseName(Panel $panel): string
    {
        if ($this->getParentResourceRegistration() instanceof ParentResourceRegistration) {
            return $this->getParentResourceInstance()->getRouteBaseName($panel) . '.' . u($this->getParentRelationshipName())->kebab()->toString();
        }

        return sprintf('sylament.%s.resources.%s', $panel->getId(), str_replace('/', '.', $this->getSlug()));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, Panel|string|null $panel = null, bool $shouldGuessMissingParameters = false): string
    {
        if (blank($name)) {
            return $this->getIndexUrl($parameters, $isAbsolute, $panel, $shouldGuessMissingParameters);
        }

        $chain = $this->getAncestorChain();
        $records = [];
        $record = $parameters['record'] ?? null;

        if (is_object($record)) {
            $records['record'] = $record;
            $parameters['record'] = $this->getRecordRouteKey($record);
        }

        foreach ($chain as $link) {
            $ancestorRecord = $parameters[$link['parameter']] ?? null;

            if (is_object($ancestorRecord)) {
                $records[$link['parameter']] = $ancestorRecord;
                $parameters[$link['parameter']] = $link['resource']->getRecordRouteKey($ancestorRecord);
            }
        }

        if ($shouldGuessMissingParameters) {
            $parameters = $this->guessAncestorParameters($chain, $parameters, $records);
        }

        return $this->getUrlGenerator()->generate(
            $this->getRouteBaseName($this->resolvePanel($panel)) . '.' . $name,
            $parameters,
            $isAbsolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    /**
     * The listing these records are reached through. A nested resource is listed
     * by its parent, so the arms run over the parent's pages — one keyed by the
     * relationship, else its view, else its edit page, all three carrying the
     * parent record, else its index, which takes no parent key — and only a
     * resource no parent lists falls through to an index page of its own.
     *
     * @param array<string, mixed> $parameters
     */
    public function getIndexUrl(array $parameters = [], bool $isAbsolute = true, Panel|string|null $panel = null, bool $shouldGuessMissingParameters = false): string
    {
        if ($this->getParentResourceRegistration() instanceof ParentResourceRegistration) {
            $parentResource = $this->getParentResourceInstance();
            $parentParameterName = $this->getParentRouteParameterName();
            $parentRecord = $parameters[$parentParameterName] ?? null;
            unset($parameters[$parentParameterName]);

            $relationshipPage = u($this->getParentRelationshipName())->kebab()->toString();

            foreach ([$relationshipPage, 'view', 'edit'] as $pageKey) {
                if ($parentResource->hasPage($pageKey)) {
                    // A null parent key is omitted, so the generator names `record`
                    // as the missing parameter rather than refusing a null one.
                    return $parentResource->getUrl($pageKey, [...$parameters, ...($parentRecord === null ? [] : ['record' => $parentRecord])], $isAbsolute, $panel, $shouldGuessMissingParameters);
                }
            }

            if ($parentResource->hasPage('index')) {
                return $parentResource->getUrl('index', $parameters, $isAbsolute, $panel, $shouldGuessMissingParameters);
            }
        }

        if (! $this->hasPage('index')) {
            throw new LogicException('The resource [' . static::class . '] does not have an [index] page. Define [getIndexUrl()] for alternative routing.');
        }

        return $this->getUrl('index', $parameters, $isAbsolute, $panel, $shouldGuessMissingParameters);
    }

    /**
     * The record sub-navigation a page of this resource renders — none by
     * default.
     *
     * @return array<NavigationItem>
     */
    public function getRecordSubNavigation(Page $page): array
    {
        return [];
    }

    /**
     * Where the band of every page of this resource is drawn: the resource's own
     * static, else the panel's knob — a page naming a position of its own still
     * wins over both.
     */
    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return static::$subNavigationPosition ?? $this->resolvePanel(null)->getSubNavigationPosition();
    }

    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(Panel $panel): array
    {
        if (! $this->hasPage('index')) {
            return [];
        }

        $routeBaseName = $this->getRouteBaseName($panel);
        $indexRouteName = $routeBaseName . '.index';

        return [
            NavigationItem::make($this->getNavigationLabel())
                ->group($this->getNavigationGroup())
                ->icon($this->getNavigationIcon())
                ->activeIcon($this->getActiveNavigationIcon())
                ->sort($this->getNavigationSort())
                ->badge($this->getNavigationBadge(), color: $this->getNavigationBadgeColor())
                ->url(fn (UrlGeneratorInterface $urlGenerator): string => $urlGenerator->generate($indexRouteName))
                ->isActiveWhen(function (RequestStack $requestStack) use ($routeBaseName): bool {
                    $route = $requestStack->getCurrentRequest()?->attributes->get('_route');

                    return is_string($route) && str_starts_with($route, $routeBaseName . '.');
                }),
        ];
    }

    public function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? $this->getTitleCasePluralRecordLabel();
    }

    public function getNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$navigationIcon;
    }

    public function getActiveNavigationIcon(): string|BackedEnum|Markup|Closure|null
    {
        return static::$activeNavigationIcon ?? $this->getNavigationIcon();
    }

    public function getNavigationSort(): ?int
    {
        return static::$navigationSort;
    }

    public function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    public function getNavigationBadge(): ?string
    {
        return null;
    }

    /**
     * @return string|array<int|string, string>|null
     */
    public function getNavigationBadgeColor(): string|array|null
    {
        return null;
    }

    public function shouldRegisterNavigation(): bool
    {
        return static::$shouldRegisterNavigation;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager ?? throw $this->missingServices();
    }

    protected function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator ?? throw $this->missingServices();
    }

    protected function getCurrentPanel(): CurrentPanel
    {
        return $this->currentPanel ?? throw $this->missingServices();
    }

    protected function getPanels(): PanelRegistry
    {
        return $this->panels ?? throw $this->missingServices();
    }

    protected function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor ?? throw $this->missingServices();
    }

    protected function getAssociationReader(): AssociationReader
    {
        return $this->associationReader ?? throw $this->missingServices();
    }

    protected function getResourceRegistry(): ResourceRegistry
    {
        return $this->resources ?? throw $this->missingServices();
    }

    private function resolvePanel(Panel|string|null $panel): Panel
    {
        if ($panel instanceof Panel) {
            return $panel;
        }

        if ($panel !== null) {
            return $this->getPanels()->get($panel)
                ?? throw new LogicException(sprintf('No panel with the ID "%s" is registered.', $panel));
        }

        return $this->getCurrentPanel()->get() ?? $this->getPanels()->getDefault();
    }

    private function missingServices(): LogicException
    {
        return new LogicException(sprintf('The resource [%s] was built outside the container, so it holds no injected services; register it as an autowired service.', static::class));
    }
}
