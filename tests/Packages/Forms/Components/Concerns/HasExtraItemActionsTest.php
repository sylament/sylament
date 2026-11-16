<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\RepeaterHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it("renders each extra action in every item's header as a small gray icon button, unless the action says otherwise", function (): void {
    $crawler = $this->renderSchemaComponent(
        Repeater::make('lines')
            ->extraItemActions([Action::make('inspect')])
            ->extraItemActions([static fn (): array => [Action::make('flag')->color('danger')->button()]])
            ->components([TextInput::make('name')]),
        ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]],
    );

    expect($crawler)->toContainButtons('.sy-fo-repeater-item-header-end-actions ' . SupportSelectors::SMALL_ICON_BUTTON . SupportSelectors::GRAY, ['Inspect', 'Inspect'])
        ->toContainButtons('.sy-fo-repeater-item-header-end-actions ' . SupportSelectors::BUTTON . SupportSelectors::color('danger'), ['Flag', 'Flag']);
});

it('renders each extra action in every row of the simple and table views', function (Closure $repeater, string $row): void {
    $crawler = $this->renderSchemaComponent($repeater()->extraItemActions([Action::make('inspect')]), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

    expect($crawler)->toContainButtons("{$row} " . SupportSelectors::SMALL_ICON_BUTTON . '[aria-label="Inspect"]', ['Inspect', 'Inspect']);
})->with([
    'simple' => [fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('name')), 'li.sy-fo-simple-repeater-item'],
    'table' => [fn (): Repeater => Repeater::make('lines')->table([TableColumn::make('Name')])->components([TextInput::make('name')]), 'tr[data-item-key]'],
]);

describe('an item-bound action', function (): void {
    it("hands the action its item's state and its repeater", function (): void {
        $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make('inspect')->schemaComponent('lines'), arguments: ['item' => 'b']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['inspect {"name":"Grace"} on lines']);
    });
});
