<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Tests\Fixtures\Schemas\OperationForm;
use Sylament\Tests\Fixtures\Schemas\StateForm;

it('disables its control when told, or when the component around it is disabled', function (): void {
    $render = $this->renderSchemaComponent(Group::make([
        TextInput::make('enabled'),
        TextInput::make('locked')->disabled(),
        Section::make('Archived')->disabled()->components([TextInput::make('inherited')]),
    ]));

    expect($render)
        ->toContainElement('input[name="enabled"]:not([disabled])')
        ->toContainElement('input[name="locked"][disabled]')
        ->toContainElement('input[name="inherited"][disabled]');
});

describe('disabledOn', function (): void {
    it('disables a component on the operation it names', function (string $field): void {
        expect($this->createLiveComponent(OperationForm::class, ['operation' => 'create'])->render()->crawler())
            ->toContainElement("input[name=\"{$field}\"]:not([disabled])")
            ->and($this->createLiveComponent(OperationForm::class, ['operation' => 'edit'])->render()->crawler())
            ->toContainElement("input[name=\"{$field}\"][disabled]");
    })->with([
        'a string' => 'disabledByString',
        'an Operation case' => 'disabledByCase',
        'a list' => 'disabledByList',
    ]);

    it('disables a component on its host class under every operation', function (): void {
        foreach (['create', 'edit'] as $operation) {
            expect($this->createLiveComponent(OperationForm::class, ['operation' => $operation])->render()->crawler())
                ->toContainElement('input[name="disabledByHost"][disabled]');
        }
    });
});

describe('saved state', function (): void {
    it('keeps a disabled field out of the saved state, though the client can still write it', function (): void {
        $tester = $this->createLiveComponent(StateForm::class)
            ->fillForm(['locked' => 'Forced', 'title' => 'Kept'])
            ->assertFormSet(['locked' => 'Forced']);

        expect($tester->component()->getSchema('form')->getStateSnapshot())
            ->toHaveKey('title', 'Kept')
            ->not->toHaveKey('locked');
    });
});
