<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateHookedDataArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditHookedDataArticle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The two record pages refuse a path their mutate hook adds that no component
 * of the form maps, and let four shapes through: an overwritten mapped key, a
 * key the client typed into the data bag, a key a Hidden field maps and a key a
 * field that is not dehydrated maps. The article "Alpha" is stored as a draft.
 * Kernel reboot is disabled because the fixture database lives in memory on a
 * container service; every value read back is a string.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, string}
 */
function pageHookDataClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Alpha')->setSlug('alpha'));

    $request = new Request();
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    return [$client, $entityManager, (string) $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha'])?->getId()];
}

/**
 * @return list<array<string, mixed>>
 */
function pageHookDataRows(): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative('SELECT title, slug, status FROM Article ORDER BY slug');
}

it('refuses by name a path mutateFormDataBeforeCreate() adds that no component of the form maps, writing nothing', function (): void {
    [$client] = pageHookDataClient();

    $component = $this->createLiveComponent(CreateHookedDataArticle::class, ['arm' => 'adding'], client: $client);
    $component->set('data', ['title' => 'gamma', 'slug' => 'gamma']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] added [status, meta.source] in mutateFormDataBeforeCreate(), but no component of its form maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or set it on the record in makeRecord() or handleRecordCreation().',
        CreateHookedDataArticle::class,
    )));

    expect(pageHookDataRows())->toBe([['title' => 'Alpha', 'slug' => 'alpha', 'status' => 'draft']]);
});

it('saves through mutateFormDataBeforeCreate() what a component maps, and writes no key the client typed', function (string $arm, array $typed, string $title, string $status): void {
    [$client, $entityManager] = pageHookDataClient();

    $component = $this->createLiveComponent(CreateHookedDataArticle::class, ['arm' => $arm], client: $client);
    $component->set('data', $typed)->call('create');

    $entityManager->clear();
    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'gamma']);

    expect($component->response()->isRedirection())->toBeTrue()
        ->and($article?->getTitle())->toBe($title)
        ->and($article?->getStatus())->toBe($status);
})->with([
    'a mapped key the hook overwrites' => ['overwriting', ['title' => 'gamma', 'slug' => 'gamma'], 'GAMMA', 'draft'],
    'a key the client typed that no component maps' => ['injected', ['title' => 'gamma', 'slug' => 'gamma', 'status' => 'published'], 'GAMMA', 'draft'],
    'a key a Hidden field maps' => ['hidden', ['title' => 'gamma', 'slug' => 'gamma'], 'gamma', 'published'],
    'a key a field that is not dehydrated maps' => ['pruned', ['title' => 'gamma', 'slug' => 'gamma'], 'gamma', 'published'],
]);

it('refuses by name a path mutateFormDataBeforeSave() adds that no component of the form maps, writing nothing', function (): void {
    [$client, , $key] = pageHookDataClient();

    $component = $this->createLiveComponent(EditHookedDataArticle::class, ['record' => $key, 'arm' => 'adding'], client: $client);
    $component->set('data', ['title' => 'Beta', 'slug' => 'alpha']);

    expect(fn (): mixed => $component->call('save'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] added [status, meta.source] in mutateFormDataBeforeSave(), but no component of its form maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or set it on the record in handleRecordUpdate().',
        EditHookedDataArticle::class,
    )));

    expect(pageHookDataRows())->toBe([['title' => 'Alpha', 'slug' => 'alpha', 'status' => 'draft']]);
});

it('saves through mutateFormDataBeforeSave() what a component maps, and writes no key the client typed', function (string $arm, array $typed, string $title, string $status): void {
    [$client, $entityManager, $key] = pageHookDataClient();

    $component = $this->createLiveComponent(EditHookedDataArticle::class, ['record' => $key, 'arm' => $arm], client: $client);
    $component->set('data', $typed)->call('save');

    $entityManager->clear();
    $article = $entityManager->find(Article::class, (int) $key);

    expect($component->response()->getStatusCode())->toBe(200)
        ->and($article?->getTitle())->toBe($title)
        ->and($article?->getStatus())->toBe($status);
})->with([
    'a mapped key the hook overwrites' => ['overwriting', ['title' => 'beta', 'slug' => 'alpha'], 'BETA', 'draft'],
    'a key the client typed that no component maps' => ['injected', ['title' => 'beta', 'slug' => 'alpha', 'status' => 'published'], 'BETA', 'draft'],
    'a key a Hidden field maps' => ['hidden', ['title' => 'beta', 'slug' => 'alpha'], 'beta', 'published'],
    'a key a field that is not dehydrated maps' => ['pruned', ['title' => 'beta', 'slug' => 'alpha'], 'beta', 'published'],
]);
