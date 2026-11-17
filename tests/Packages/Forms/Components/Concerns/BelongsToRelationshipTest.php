<?php

declare(strict_types=1);

use Sylament\Forms\Components\CheckboxList;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;

it('refuses a relationship whose options it cannot enumerate, naming the field and what is missing, rather than answering an empty map', function (bool $withoutManager, ?string $recordClass, string $field, string $reason): void {
    $schema = new RecordMappingHost()->mapping(CheckboxList::make($field)->relationship(titleAttribute: 'name'));

    if ($recordClass !== null) {
        $schema->record($recordClass);
    }

    [$list] = $schema->getComponents();

    if ($withoutManager) {
        $list->setAssociationReader(new AssociationReader());
    }

    expect(fn (): array => $list->getOptions())->toThrow(LogicException::class, "The [{$field}] field declares relationship(\"{$field}\"), but {$reason} or replace relationship() with an options() map.");
})->with([
    'no entity manager' => [true, Garden::class, 'plants', 'no Doctrine EntityManager backs it, so its options could only ever come back empty — and an empty option list silently rejects every submitted value. Register Doctrine (doctrine/doctrine-bundle),'],
    'no record class' => [false, null, 'plants', 'the schema names no record class to read the association off. Give the schema a record() instance or class-string,'],
    'no association at the property' => [false, Garden::class, 'name', '[' . Garden::class . '] maps no association at "name". Name the property Doctrine maps,'],
]);

it('refuses a relationship whose name resolves to nothing, naming the field', function (): void {
    [$list] = new RecordMappingHost()->mapping(CheckboxList::make('plants')->relationship(static fn (): ?string => null, 'name'))->record(Garden::class)->getComponents();

    expect(fn (): array => $list->getOptions())->toThrow(LogicException::class, 'The [plants] field was asked for its relationship, but relationship() named none and the field carries no name to fall back on.');
});
