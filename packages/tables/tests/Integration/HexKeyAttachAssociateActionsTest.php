<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Select;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Tests\Fixtures\Doctrine\HexKeyRig;
use Sylament\Tables\Tests\Fixtures\Twig\Components\HexOwnerRelationTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * `AttachAction` and `AssociateAction` over a target keyed through a scalar type
 * whose reader does not read its own output back unchanged: the lenient hex
 * reader, which reads the column's 255 as `ff` and `ff` as `0`, and the strict
 * prefixed one, which refuses an unprefixed value. The option projection is an
 * array result, whose hydrator has already read each key column through its
 * type, so an option keyed by reading it a second time offers `0`, or throws.
 * It sits in the tables suite for the reason `CompositeAttachAssociateActionsTest`
 * does. Every expected key is a literal.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function hexKeySeededClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    HexKeyRig::seed($entityManager);

    return [$client, $entityManager];
}

function hexKeyHost(KernelBrowser $client, string $relationship, string $action): InteractsWithActions&InteractsWithTable
{
    $host = test()->createLiveComponent(HexOwnerRelationTable::class, ['relationship' => $relationship, 'action' => $action], client: $client)->component();

    return $host instanceof InteractsWithActions && $host instanceof InteractsWithTable
        ? $host
        : throw new LogicException('The hex host is not an action-hosting table.');
}

function hexKeyMountedSelect(KernelBrowser $client, string $relationship, string $action): Select
{
    $host = hexKeyHost($client, $relationship, $action);
    $host->mountAction($action, [], ['table' => true]);

    $select = $host->getMountedActionSchema()?->getComponent('recordId');

    return $select instanceof Select ? $select : throw new LogicException("The [{$action}] modal carries no record select.");
}

it('offers a target whose identifier type misreads its own output by the key the record mints', function (string $relationship, string $action, array $expected): void {
    [$client] = hexKeySeededClient();

    expect(hexKeyMountedSelect($client, $relationship, $action)->getSearchResults(''))->toBe($expected);
})->with([
    'attach, one field' => ['swatches', 'attach', ['ff' => 'Ivory', '1a' => 'Umber']],
    'attach, two fields' => ['tiles', 'attach', ['ff~north' => 'Ivory north', 'ff~south' => 'Ivory south']],
    'associate, one field' => ['keptSwatches', 'associate', ['ff' => 'Ivory', '1a' => 'Umber']],
    'associate, two fields' => ['keptTiles', 'associate', ['ff~north' => 'Ivory north', 'ff~south' => 'Ivory south']],
]);

it('attaches or associates the row an offered key names', function (string $relationship, string $action, string $key, array $held): void {
    [$client, $entityManager] = hexKeySeededClient();

    $offered = array_keys(hexKeyMountedSelect($client, $relationship, $action)->getSearchResults(''));

    expect($offered)->toContain($key);

    $host = hexKeyHost($client, $relationship, $action);
    $host->mountAction($action, [], ['table' => true]);
    $host->getMountedActionSchema()?->fill(['recordId' => $key]);
    $host->callMountedAction();

    $entityManager->clear();

    expect(array_keys(hexKeyHost($client, $relationship, $action)->getTable()->getRecords()))->toBe($held)
        ->and($host->getMountedAction())->toBeNull();
})->with([
    'attach, one field' => ['swatches', 'attach', 'ff', ['c', 'ff']],
    'attach, two fields' => ['tiles', 'attach', 'ff~north', ['1a~north', 'ff~north']],
    'associate, one field' => ['keptSwatches', 'associate', 'ff', ['c', 'ff']],
    'associate, two fields' => ['keptTiles', 'associate', 'ff~south', ['1a~north', 'ff~south']],
]);

it('offers a target whose identifier type refuses its own output without throwing, searched or not', function (string $search, array $expected): void {
    [$client] = hexKeySeededClient();

    expect(hexKeyMountedSelect($client, 'markedTags', 'attach')->getSearchResults($search))->toBe($expected);
})->with([
    'unsearched' => ['', ['alpha' => 'Alpha', 'beta' => 'Beta']],
    'searched' => ['alp', ['alpha' => 'Alpha']],
]);
