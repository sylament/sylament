<?php

declare(strict_types=1);

use Sylament\Forms\Components\Concerns\CanBeReadOnly;
use Sylament\Tests\Fixtures\Forms\ReadOnlyForm;
use Sylament\Tests\Infrastructure\ComponentRegistry;

describe('readOnly()', function (): void {
    it('renders readonly on the control of every field that offers it, and not when the condition is false', function (string $field): void {
        expect($this->renderSchemaComponent($field::make('reference')->readOnly()))
            ->toContainElement('[name="reference"][readonly]:not([disabled])')
            ->and($this->renderSchemaComponent($field::make('reference')->readOnly(false)))
            ->toContainElement('[name="reference"]:not([readonly])');
    })->with(fn (): array => ComponentRegistry::componentsUsing(CanBeReadOnly::class));

    it('keeps a read-only field in the saved state and validates it like a writable one', function (): void {
        $tester = $this->createLiveComponent(ReadOnlyForm::class)->fillForm(['reference' => 'R-1', 'title' => 'Kept']);

        expect($tester->component()->getSchema('form')->getStateSnapshot())
            ->toHaveKey('reference', 'R-1')
            ->toHaveKey('title', 'Kept');

        $this->createLiveComponent(ReadOnlyForm::class)
            ->fillForm(['reference' => ''])
            ->call('save')
            ->assertHasFormErrors(['reference']);
    });
});

describe('readOnlyOn()', function (): void {
    it('makes a field read-only on the operation it names, and not on another', function (string $field): void {
        expect($this->createLiveComponent(ReadOnlyForm::class, ['operation' => 'create'])->render()->crawler())
            ->toContainElement("input[name=\"{$field}\"]:not([readonly])")
            ->and($this->createLiveComponent(ReadOnlyForm::class, ['operation' => 'edit'])->render()->crawler())
            ->toContainElement("input[name=\"{$field}\"][readonly]");
    })->with([
        'a string' => 'readOnlyByString',
        'an Operation case' => 'readOnlyByCase',
        'a list' => 'readOnlyByList',
    ]);

    it('makes a field read-only on its host class under every operation', function (): void {
        foreach (['create', 'edit'] as $operation) {
            expect($this->createLiveComponent(ReadOnlyForm::class, ['operation' => $operation])->render()->crawler())
                ->toContainElement('input[name="readOnlyByHost"][readonly]');
        }
    });
});
