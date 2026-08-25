<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Actions\BulkAction;
use Sylament\Actions\BulkActionGroup;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Sylament\Actions\ViewAction;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it('maps the built assets directory into AssetMapper under @sylament/actions', function (): void {
    // StimulusBundle's `ControllersMapGenerator` resolves a `symfony.controllers` entry by
    // calling `getAssetFromSourcePath()` on the package directory plus the declared `main`,
    // and throws when the path sits outside every registered one — so the prepend is what
    // stands between the built controller and a consuming app.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/controllers/dispatch_controller.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/actions/controllers/dispatch_controller.js');
});

it('registers the prebuilt actions as non-shared services, so their #[Required] setters run', function (): void {
    // An action class the bundle does not `->set()` falls back to `new $class()` in
    // `ComponentResolver::instantiate()`, and every `#[Required]` injection on it — the
    // manager registry, the event dispatcher — silently never runs.
    $container = $this::getContainer();

    expect($container->has(BulkAction::class))->toBeTrue()
        ->and($container->has(BulkActionGroup::class))->toBeTrue()
        ->and($container->has(ViewAction::class))->toBeTrue()
        ->and($container->has(ReplicateAction::class))->toBeTrue()
        ->and($container->get(BulkAction::class))->toBeInstanceOf(BulkAction::class)
        ->and($container->get(BulkAction::class))->not->toBe($container->get(BulkAction::class))
        ->and($container->get(ViewAction::class))->toBeInstanceOf(ViewAction::class)
        ->and($container->get(ViewAction::class))->not->toBe($container->get(ViewAction::class))
        ->and($container->get(ReplicateAction::class))->toBeInstanceOf(ReplicateAction::class)
        ->and($container->get(ReplicateAction::class))->not->toBe($container->get(ReplicateAction::class));
});

it('boots the fixture entity manager, registry and notifications', function (): void {
    $container = $this::getContainer();

    $entityManager = $container->get(EntityManagerInterface::class);

    $note = new Note();
    $note->setTitle('Rig');

    $entityManager->persist($note);
    $entityManager->flush();
    $entityManager->clear();

    $reread = $entityManager->getRepository(Note::class)->findOneBy(['title' => 'Rig']);

    expect($reread)->toBeInstanceOf(Note::class)
        ->and($container->get(ManagerRegistry::class))->toBeInstanceOf(FixtureManagerRegistry::class)
        ->and($container->get(ManagerRegistry::class)->getManagerForClass(Note::class))->toBe($entityManager);

    $request = new Request();
    $request->setSession($container->get('session.factory')->createSession());
    $container->get(RequestStack::class)->push($request);

    Notification::make('rig')->title('Saved')->send();

    expect($container->get(SessionNotifications::class)->pull())->toHaveCount(1);
});
