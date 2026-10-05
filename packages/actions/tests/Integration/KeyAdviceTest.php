<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\ConstructorKeyedSeat;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Twig\Components\KeyAdviceHost;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * What a refused create or replicate tells the developer to do: build a record
 * whose constructor takes arguments in using(), and switch the key rules back
 * on when a mapped key field reaches persist() blank. In-process on one host
 * object, for the reason `ReplicateActionTest` states; each case pushes a
 * request carrying a session for the success toast.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());

    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * @param array<string, mixed> $typed
 * @param array<string, mixed> $context
 */
function keyAdviceMounted(string $action, array $typed, array $context = []): KeyAdviceHost
{
    $host = test()->createLiveComponent(KeyAdviceHost::class)->component();
    assert($host instanceof KeyAdviceHost);

    $host->mountAction($action, [], $context);

    foreach ($typed as $key => $value) {
        $host->mountedActionsData[0][$key] = $value;
    }

    return $host;
}

/**
 * @return list<array<string, mixed>>
 */
function keyAdviceRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

it('names validateRecordKey() in the blank-key refusal when the modal switches the key rules off, writing nothing', function (): void {
    $host = keyAdviceMounted('createUncheckedRedirect', ['host' => '   ', 'path' => '   ', 'target' => '/new']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, sprintf(
        "The [createUncheckedRedirect] action built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a field in the action's schema() — a Hidden one for a value the user should not type — or fill it in a prePersist listener. The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.",
        Redirect::class,
    ));

    test()->getContainer()->get(EntityManagerInterface::class)->flush();

    expect(keyAdviceRows('SELECT host, path, target FROM Redirect'))->toBe([]);
});

it('refuses the same blank pair under its first field while the key rules stay on', function (): void {
    $host = keyAdviceMounted('createCheckedRedirect', ['host' => '   ', 'path' => '   ', 'target' => '/new']);

    try {
        $host->callMountedAction();
        test()->fail('Expected the create to be refused with a ValidationException.');
    } catch (ValidationException $validationException) {
        expect($validationException->getErrors())->toBe(['mountedActionsData.0.host' => ['This value should not be blank.']]);
    }

    expect(keyAdviceRows('SELECT host, path, target FROM Redirect'))->toBe([]);
});

it('refuses by name, naming using(), a plain create of a class whose constructor requires arguments', function (): void {
    $host = keyAdviceMounted('createKeyedSeat', ['code' => 'C3']);

    expect(fn (): mixed => $host->callMountedAction())->toThrow(LogicException::class, sprintf(
        "Cannot map a new record for the [createKeyedSeat] action: %s requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or build it in the action's using() closure.",
        ConstructorKeyedSeat::class,
    ))
        ->and(keyAdviceRows('SELECT code FROM ConstructorKeyedSeat'))->toBe([]);
});

it('refuses by name a modal replicate of a record whose key its constructor sets, writing nothing', function (): void {
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist(new ConstructorKeyedSeat('S1'));
    $entityManager->flush();

    $host = keyAdviceMounted('replicateKeyedSeat', ['code' => 'S2'], ['recordKey' => 'S1']);

    try {
        $host->callMountedAction();
        test()->fail('Expected the replicate to be refused by name.');
    } catch (LogicException $logicException) {
        expect($logicException->getMessage())->toContain(ConstructorKeyedSeat::class)
            ->toContain('code')
            ->not->toStartWith('Cannot map a new record');
    }

    expect(keyAdviceRows('SELECT code FROM ConstructorKeyedSeat'))->toBe([['code' => 'S1']]);
});
