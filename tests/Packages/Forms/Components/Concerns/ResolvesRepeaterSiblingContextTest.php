<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Schemas\RepeaterForm;

describe('resolveRepeaterSiblingContext()', function (): void {
    it("answers a field in a repeater item with its repeater, its item, its path in the item, the items' prefix and the other items", function (array $members, string $itemKey, array $siblingKeys): void {
        $schema = $this->createLiveComponent(RepeaterForm::class, ['data' => ['members' => $members]])->component()->getSchema('form');
        $repeater = $schema->getComponent(static fn (object $component): bool => $component instanceof Repeater);
        $field = $repeater?->getChildSchema($itemKey)?->getComponent(static fn (object $component): bool => $component instanceof TextInput);
        $context = $field?->resolveRepeaterSiblingContext();

        expect($context?->repeater)->toBe($repeater)
            ->and([$context?->itemKey, $context?->fieldPath, $context?->itemsPrefix, $context?->siblingKeys])
            ->toBe([$itemKey, 'name', 'data.members.', $siblingKeys]);
    })->with([
        'a plain key' => [['ada' => ['name' => 'Ada'], 'grace' => ['name' => 'Grace']], 'grace', ['ada']],
        'a key holding a dot' => [['ada.l' => ['name' => 'Ada'], 'grace' => ['name' => 'Grace'], 'mary' => ['name' => 'Mary']], 'ada.l', ['grace', 'mary']],
    ]);

    it('answers nothing for a field outside a repeater, or in the only item', function (): void {
        $outside = $this->createLiveComponent(RepeaterForm::class)->component()->getSchema('form')->getComponent('team');
        $lone = $this->createLiveComponent(RepeaterForm::class, ['data' => ['members' => ['ada' => ['name' => 'Ada']]]])->component()->getSchema('form');
        $loneField = $lone->getComponent(static fn (object $component): bool => $component instanceof Repeater)?->getChildSchema('ada')?->getComponent(static fn (object $component): bool => $component instanceof TextInput);

        expect($outside)->toBeInstanceOf(TextInput::class)
            ->and($outside?->resolveRepeaterSiblingContext())->toBeNull()
            ->and($loneField)->toBeInstanceOf(TextInput::class)
            ->and($loneField?->resolveRepeaterSiblingContext())->toBeNull();
    });
});
