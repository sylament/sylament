<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\ClosureForm;

describe('closure injection', function (): void {
    it('hands a closure the component itself, by name or by its class', function (): void {
        $fieldset = Fieldset::make()
            ->id('billing')
            ->label(static fn ($component, Fieldset $fieldset): string => ($component === $fieldset) ? "Fieldset {$fieldset->getId()}" : 'Two components')
            ->components([Text::make('a')]);

        expect($this->renderSchemaComponent($fieldset))->toContainElement('legend', 'Fieldset billing');
    });

    it('hands a closure the state, a reader, the record, the operation and the host by name', function (): void {
        expect($this->createLiveComponent(ClosureForm::class)->render()->crawler())
            ->toContainElement('label', 'State Draft, raw Draft')
            ->toContainElement('label', 'Under Draft')
            ->toContainElement('label', 'By Minutes')
            ->toContainElement('label', 'edit and edit')
            ->toContainElement('label', 'This host');
    });

    it('hands a closure the raw state as it is stored: null where no state path leads, and the empty string a field holds', function (): void {
        $fieldset = Fieldset::make()
            ->label(static fn ($rawState): string => ($rawState === null) ? 'Raw null' : 'Raw ' . get_debug_type($rawState))
            ->components([Text::make('a')]);

        expect($this->renderViewComponent(Schema::make()->components([$fieldset])))->toContainElement('legend', 'Raw null')
            ->and($this->createLiveComponent(ClosureForm::class)->render()->crawler())->toContainElement('label', 'Raw empty string');
    });

    it('hands a closure a writer by name', function (): void {
        $this->createLiveComponent(ClosureForm::class)
            ->fillForm(['title' => 'Final'])
            ->assertFormSet(['slug' => 'Final slug']);
    });
});

describe('closure injection by type', function (): void {
    it('hands a closure a reader, and the record by its class or an interface it implements', function (): void {
        expect($this->createLiveComponent(ClosureForm::class)->render()->crawler())
            ->toContainElement('label', 'Read Draft')
            ->toContainElement('label', 'Memo Minutes')
            ->toContainElement('label', 'Note Minutes');
    });

    it('hands a closure a writer by its type', function (): void {
        $this->createLiveComponent(ClosureForm::class)
            ->fillForm(['subtitle' => 'Second'])
            ->assertFormSet(['summary' => 'Second summary']);
    });
});
