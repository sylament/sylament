<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tables\Enums\ColumnManagerResetActionPosition;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('heads the panel Columns at the level it is given, above the items, with no footer while there is neither an Apply nor a footer Reset', function (): void {
    $crawler = $this->renderTwigComponent('SylamentTables:ColumnManager', [
        'headingLevel' => 4,
        'items' => [['name' => 'code', 'label' => 'Code', 'isToggleable' => true, 'isToggled' => true]],
        'applyAction' => Action::make('applyTableColumnManager')->label('Apply columns'),
    ])->crawler();

    expect($crawler)->toContainElement('h4', 'Columns')
        ->and($crawler)->toContainElement(Selectors::COLUMN_MANAGER . ' ' . Selectors::COLUMN_MANAGER_ITEM_LABEL, 'Code')
        ->and($crawler)->toContainElement(SupportSelectors::LINK . Selectors::COLUMN_MANAGER_RESET, 'Reset')
        ->and($crawler->filter(Selectors::COLUMN_MANAGER_PANEL_FOOTER))->toHaveCount(0);
});

it('offers the Reset as a link beside the heading, or as a button after Apply in its footer', function (ColumnManagerResetActionPosition $position, array $footer, int $headerLinks): void {
    $crawler = $this->renderTwigComponent('SylamentTables:ColumnManager', [
        'resetActionPosition' => $position,
        'applyVisible' => true,
        'applyAction' => Action::make('applyTableColumnManager')->label('Apply columns')->action('applyTableColumnManager'),
    ])->crawler();

    expect($crawler)->toContainButtons(Selectors::COLUMN_MANAGER_PANEL_FOOTER . ' button', $footer)
        ->and($crawler->filter(SupportSelectors::LINK . Selectors::COLUMN_MANAGER_RESET))->toHaveCount($headerLinks);
})->with([
    'the header' => [ColumnManagerResetActionPosition::Header, ['Apply columns'], 1],
    'the footer' => [ColumnManagerResetActionPosition::Footer, ['Apply columns', 'Reset'], 0],
]);
