<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources;

use BackedEnum;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Stringable;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource\Concerns\HasGlobalSearch;
use Sylament\Schemas\Schema;
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

    private ?EntityManagerInterface $entityManager = null;

    private ?UrlGeneratorInterface $urlGenerator = null;

    private ?CurrentPanel $currentPanel = null;

    private ?PanelRegistry $panels = null;

    private ?PropertyAccessor $propertyAccessor = null;

    #[Required]
    public function setResourceServices(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CurrentPanel $currentPanel,
        PanelRegistry $panels,
        PropertyAccessor $propertyAccessor,
    ): void {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->currentPanel = $currentPanel;
        $this->panels = $panels;
        $this->propertyAccessor = $propertyAccessor;
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

    public function resolveRecordRouteBinding(int|string $key): ?object
    {
        $query = clone $this->getRecordQuery();

        $rootAlias = $query->getRootAliases()[0]
            ?? throw new LogicException(sprintf('The record query of resource [%s] names no root alias to resolve a record against.', static::class));

        $field = $this->getRecordRouteKeyName()
            ?? $this->getSingleIdentifierMetadata($query->getEntityManager())->getSingleIdentifierFieldName();

        $record = $query
            ->andWhere(sprintf('%s.%s = :sylament_record_key', $rootAlias, $field))
            ->setParameter('sylament_record_key', $key)
            ->getQuery()
            ->getOneOrNullResult();

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

        if ($keyName !== null) {
            $key = $this->getPropertyAccessor()->getFromRecord($record, $keyName);
        } else {
            $key = array_first($this->getSingleIdentifierMetadata($this->getEntityManager())->getIdentifierValues($record));
        }

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
        return sprintf('sylament.%s.resources.%s', $panel->getId(), str_replace('/', '.', $this->getSlug()));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, Panel|string|null $panel = null): string
    {
        if (blank($name)) {
            return $this->getIndexUrl($parameters, $isAbsolute, $panel);
        }

        $record = $parameters['record'] ?? null;

        if (is_object($record)) {
            $parameters['record'] = $this->getRecordRouteKey($record);
        }

        return $this->getUrlGenerator()->generate(
            $this->getRouteBaseName($this->resolvePanel($panel)) . '.' . $name,
            $parameters,
            $isAbsolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getIndexUrl(array $parameters = [], bool $isAbsolute = true, Panel|string|null $panel = null): string
    {
        if (! $this->hasPage('index')) {
            throw new LogicException('The resource [' . static::class . '] does not have an [index] page. Define [getIndexUrl()] for alternative routing.');
        }

        return $this->getUrl('index', $parameters, $isAbsolute, $panel);
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

    /**
     * @return ClassMetadata<object>
     */
    private function getSingleIdentifierMetadata(EntityManagerInterface $entityManager): ClassMetadata
    {
        $recordClass = $this->getRecordClass();
        $metadata = $entityManager->getClassMetadata($recordClass);

        if ($metadata->isIdentifierComposite) {
            throw new LogicException(sprintf('Records of [%s] carry a composite identifier, which a resource cannot key; use single-identifier entities or set the $recordRouteKeyName static property.', $recordClass));
        }

        return $metadata;
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
