<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Support\ArrayRecord;
use Sylament\Tests\Fixtures\Entity\Product;

describe('toArray()', function (): void {
    it("is the descriptor the host's mountAction() takes, an action's name and an empty context", function (): void {
        expect(TestAction::make('archive')->toArray())->toBe(['name' => 'archive', 'context' => []]);
    });

    it('names a schema component in the context, prefixed by the schema it gives or the default one', function (TestAction $action, ?string $defaultSchema, array $context): void {
        expect($action->toArray($defaultSchema)['context'])->toBe($context);
    })->with([
        'a component, no schema' => [TestAction::make('clear')->schemaComponent('code'), null, ['schemaComponent' => 'code']],
        'a component in the default schema' => [TestAction::make('clear')->schemaComponent('code'), 'form', ['schemaComponent' => 'form.code']],
        'a component in its own schema' => [TestAction::make('clear')->schemaComponent('code', schema: 'filters'), 'form', ['schemaComponent' => 'filters.code']],
        'the schema itself' => [TestAction::make('clear')->schemaComponent(), 'form', ['schemaComponent' => 'form']],
        'no component' => [TestAction::make('clear')->schemaComponent(false), 'form', []],
    ]);

    it('names a table action in the context, with the record key a scalar or an array record gives', function (mixed $record, array $context): void {
        expect(TestAction::make('open')->table($record)->toArray()['context'])->toBe($context);
    })->with([
        'no record' => [true, ['table' => true]],
        'a key' => [5, ['table' => true, 'recordKey' => 5]],
        'an array record' => [[ArrayRecord::getKeyName() => 'row-5', 'name' => 'Lamp'], ['table' => true, 'recordKey' => 'row-5']],
    ]);

    it('keeps a record object in the context, for the table host to key', function (): void {
        $record = new Product('Lamp');

        expect(TestAction::make('open')->table($record)->toArray()['context'])->toBe(['table' => true, 'recordKey' => $record]);
    });
});
