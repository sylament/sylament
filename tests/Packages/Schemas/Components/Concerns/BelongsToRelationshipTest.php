<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;

it("refuses a relationship the mapping cannot resolve as it fills, naming the cause in a layout component's words", function (bool $withoutManager, string $name, string $reason): void {
    $lighthouse = new Lighthouse();
    $group = Group::make([TextInput::make('keeper')])->relationship($name);

    if ($withoutManager) {
        $group->setAssociationReader(new AssociationReader());
    }

    $schema = new RecordMappingHost()->mapping($group)->record($lighthouse);

    expect(fn (): mixed => $schema->fill($lighthouse))->toThrow(
        LogicException::class,
        Group::class . ' declares relationship("' . $name . '"), but ' . $reason . ' or drop relationship() and map the related record by hand after getData().',
    );
})->with([
    'no entity manager' => [true, 'log', 'no Doctrine EntityManager backs it, so the related record could neither be read nor written. Register Doctrine (doctrine/doctrine-bundle),'],
    'no association at the property' => [false, 'name', '[' . Lighthouse::class . '] maps no association at "name". Name the property Doctrine maps,'],
]);

it('refuses to read a relationship off a record it cannot name the class of, or one never declared', function (Closure $group, string $message): void {
    $group = $group();
    new RecordMappingHost()->mapping($group);

    expect(fn (): array => $group->getStateFromRelationship(new Lighthouse()))->toThrow(LogicException::class, $message);
})->with([
    'a schema bound to no record' => [
        fn (): Group => Group::make([TextInput::make('keeper')])->relationship('log'),
        Group::class . ' declares relationship("log"), but the schema names no record class to read the association off. Give the schema a record() instance or class-string, or drop relationship() and map the related record by hand after getData().',
    ],
    'no relationship declared' => [
        fn (): Group => Group::make([TextInput::make('keeper')]),
        Group::class . ' was asked for its relationship, but relationship() named none.',
    ],
]);
