<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\HintActionForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

use function Symfony\Component\Translation\t;

describe('hint', function (): void {
    it('shows its text, coloured, its icon with its tooltip, and its actions beside the label', function (): void {
        $render = $this->renderSchemaComponent(TextInput::make('password')
            ->hint('Forgotten?')
            ->hintColor('danger')
            ->hintIcon('support:star', tooltip: 'Kept private')
            ->hintActions([Action::make('reset'), static fn (): null => null, Action::make('show')]));

        expect($render)
            ->toContainElement(Selectors::LABEL_ROW . ' ' . SchemasSelectors::TEXT . SupportSelectors::color('danger'), 'Forgotten?')
            ->toContainElement(Selectors::LABEL_ROW . ' svg' . SchemasSelectors::ICON . SupportSelectors::color('danger') . '[title="Kept private"]', 'Star')
            ->toContainButtons(Selectors::LABEL_ROW . ' button', ['Reset', 'Show'])
            ->and($render->filter(Selectors::LABEL_ROW)->text())->toBe('Password Forgotten? Star Reset Show');
    });

    it('shows each part only when it is set', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('password')->hintIcon('support:star')))
            ->toContainElement(Selectors::LABEL_ROW . ' svg' . SchemasSelectors::ICON, 'Star')
            ->not->toContainElement(SchemasSelectors::TEXT)
            ->and($this->renderSchemaComponent(TextInput::make('password')->hint('Forgotten?')))
            ->toContainElement(Selectors::LABEL_ROW . ' ' . SchemasSelectors::TEXT, 'Forgotten?')
            ->not->toContainElement('svg');
    });

    it('shows its hint whether its label is hidden or absent', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('password')->hiddenLabel()->hint('Forgotten?')))
            ->toContainElement(Selectors::LABEL_ROW . ' ' . SchemasSelectors::TEXT, 'Forgotten?')
            ->and($this->renderSchemaComponent(TextInput::make('password')->name(null)->hint('Forgotten?')))
            ->toContainElement(Selectors::LABEL_ROW . ' ' . SchemasSelectors::TEXT, 'Forgotten?');
    });

    it('runs a hint action when it is clicked', function (): void {
        $this->createLiveComponent(HintActionForm::class)
            ->assertFormSet(['code' => null])
            ->callAction(TestAction::make('suggest')->schemaComponent('code'))
            ->assertFormSet(['code' => 'X9']);
    });
});

it('translates a hint and its icon tooltip given as messages', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('password')->hint(t('field.hint', [], 'fixtures-forms'))->hintIcon('support:star', tooltip: t('field.hint_tooltip', [], 'fixtures-forms'))))
        ->toContainElement(Selectors::LABEL_ROW . ' ' . SchemasSelectors::TEXT, 'Forgotten? (translated)')
        ->toContainElement(Selectors::LABEL_ROW . ' svg' . SchemasSelectors::ICON . '[title="Kept private (translated)"]', 'Star');
});
