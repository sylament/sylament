<?php

declare(strict_types=1);

use Sylament\Actions\ViewAction;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\LinkedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("makes each cell of a record's row a button sending the record action with the record's key", function (string $recordAction, array $parameters): void {
    $this->seed($jar = new LarderJar('Apricot'));
    $key = (string) $jar->id;

    $button = $this->createLiveComponent(LinkedLarderJarTable::class, ['clickAction' => $recordAction])
        ->render()
        ->crawler()
        ->filter(Selectors::cell($key, 'label') . ' ' . Selectors::BUTTON_CELL_WRAPPER);

    expect($button)->toHaveCount(1);

    foreach ($parameters as $parameter => $value) {
        expect($button->attr("data-live-{$parameter}-param"))->toBe(str_replace('{key}', $key, $value));
    }
})->with([
    'a table action by name' => ['view', ['action' => 'mountAction', 'name' => 'view', 'context' => '{"recordKey":"{key}","table":true}']],
    'a table action by class' => [ViewAction::class, ['action' => 'mountAction', 'name' => 'view', 'context' => '{"recordKey":"{key}","table":true}']],
    "a live action of the table's host" => ['inspectJar', ['action' => 'inspectJar', 'record-key' => '{key}']],
]);

it('makes no button of a cell without a record action or a URL', function (): void {
    $this->seed($jar = new LarderJar('Apricot'));

    $cell = $this->createLiveComponent(LinkedLarderJarTable::class, ['clickAction' => ''])
        ->render()
        ->crawler()
        ->filter(Selectors::cell((string) $jar->id, 'label'));

    expect($cell->filter(Selectors::CELL_WRAPPER))->toHaveCount(1)
        ->and($cell->filter(Selectors::BUTTON_CELL_WRAPPER . ', ' . Selectors::LINKED_CELL_WRAPPER))->toHaveCount(0);
});
