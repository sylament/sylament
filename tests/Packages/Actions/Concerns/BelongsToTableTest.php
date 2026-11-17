<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it("reads the table it is bound to, else its group's, else none", function (): void {
    $table = Table::make(new class implements InteractsWithTable {
        use HasTable;
    })->heading('Products');
    $labelled = static fn (string $name): Action => Action::make($name)->label(static fn (?Table $table): string => (string) ($table?->getHeading() ?? 'No table'));

    expect($this->renderViewComponent($labelled('archive')->table($table)))->toContainElement(SupportSelectors::BUTTON, 'Products')
        ->and($this->renderViewComponent(ActionGroup::make([$labelled('archive')])->table($table)))->toContainElement(SupportSelectors::DROPDOWN_ITEM, 'Products')
        ->and($this->renderViewComponent($labelled('archive')))->toContainElement(SupportSelectors::BUTTON, 'No table');
});
