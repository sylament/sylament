<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\CurrentPanel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\NestedPanelProvider;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The chapter → verse → line vertical, wired by hand for tests that boot no
 * kernel: the three resources holding the services a container would inject,
 * and the routes the nesting is mounted at.
 */
final class NestedResourceRig
{
    /**
     * The route paths `PanelRouteLoader` mounts the nesting at, so a URL a test
     * generates here is the one the loader would answer.
     */
    private const array ROUTES = [
        'sylament.nest.resources.chapters.index' => '/nest/chapters',
        'sylament.nest.resources.chapters.create' => '/nest/chapters/create',
        'sylament.nest.resources.chapters.edit' => '/nest/chapters/{record}/edit',
        'sylament.nest.resources.chapters.verses.create' => '/nest/chapters/{chapter}/verses/create',
        'sylament.nest.resources.chapters.verses.edit' => '/nest/chapters/{chapter}/verses/{record}/edit',
        'sylament.nest.resources.chapters.verses.lines.index' => '/nest/chapters/{chapter}/verses/{verse}/lines',
        'sylament.nest.resources.chapters.verses.lines.create' => '/nest/chapters/{chapter}/verses/{verse}/lines/create',
        'sylament.nest.resources.chapters.verses.lines.edit' => '/nest/chapters/{chapter}/verses/{verse}/lines/{record}/edit',
    ];

    public static function registry(EntityManagerInterface $entityManager): ResourceRegistry
    {
        $resources = [
            ChapterResource::class => new ChapterResource(),
            VerseResource::class => new VerseResource(),
            LineResource::class => new LineResource(),
        ];

        $registry = new ResourceRegistry(new ServiceLocator(array_map(
            static fn (Resource $resource): Closure => static fn (): Resource => $resource,
            $resources,
        )));

        $routes = new RouteCollection();

        foreach (self::ROUTES as $name => $path) {
            $routes->add($name, new Route($path));
        }

        $panels = new PanelRegistry([new NestedPanelProvider()]);
        $propertyAccessor = new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());

        foreach ($resources as $resource) {
            $resource->setResourceServices(
                $entityManager,
                new UrlGenerator($routes, new RequestContext()),
                new CurrentPanel(new RequestStack(), $panels),
                $panels,
                $propertyAccessor,
                new AssociationReader($entityManager, $propertyAccessor),
                $registry,
            );
        }

        return $registry;
    }

    /**
     * @template T of Page
     *
     * @param class-string<T>       $pageClass
     * @param array<string, string> $parentRecords
     *
     * @return T
     */
    public static function page(EntityManagerInterface $entityManager, string $pageClass, array $parentRecords = []): Page
    {
        $page = new $pageClass();
        $page->setResourceRegistry(self::registry($entityManager));
        $page->parentRecords = $parentRecords;

        return $page;
    }

    /**
     * One chapter, one of its verses and one of that verse's lines, flushed so
     * each carries the identifier a URL keys it by.
     *
     * @return array{Chapter, Verse, Line}
     */
    public static function records(EntityManagerInterface $entityManager, string $title = 'Genesis'): array
    {
        $chapter = new Chapter()->setTitle($title);
        $verse = new Verse()->setHeading('In the beginning')->setChapter($chapter);
        $line = new Line()->setContent('Let there be light')->setVerse($verse);

        $entityManager->persist($chapter);
        $entityManager->persist($verse);
        $entityManager->persist($line);
        $entityManager->flush();

        return [$chapter, $verse, $line];
    }
}
