<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\BulkActionGroup;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('triggers its dropdown from a gray button reading "Bulk actions" beside the ellipsis, opening below and to the start', function (): void {
    expect($this->renderViewComponent(BulkActionGroup::make([Action::make('delete')])))
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::GRAY, 'ellipsis-vertical-20-solidBulk actions')
        ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('placement', 'bottom-start'));
});

it('marks its dropdown bulk-only beside the dropdown attributes it is given', function (): void {
    expect($this->renderViewComponent(BulkActionGroup::make([Action::make('delete')])->extraDropdownAttributes(['data-tour' => 'bulk'])))
        ->toContainElement(SupportSelectors::DROPDOWN . Selectors::BULK_ONLY . '[data-tour="bulk"]');
});

it("becomes a row of a parent group's dropdown", function (): void {
    expect($this->renderViewComponent(ActionGroup::make([BulkActionGroup::make([Action::make('delete')])])))
        ->toContainElement(SupportSelectors::DROPDOWN_ITEM . Selectors::TRIGGER, 'ellipsis-vertical-20-solidBulk actions');
});
