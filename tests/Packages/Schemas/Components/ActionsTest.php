<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Schemas\Components\Actions;
use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Schemas\EmbeddedSchemaHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('shows its label above a row of its actions in order, the actions of a later call replacing those it was made with', function (): void {
    $render = $this->renderSchemaComponent(Actions::make([Action::make('draft')])
        ->actions([Action::make('save'), Action::make('cancel')->label('Back')])
        ->label('Next steps')
        ->id('steps'));

    expect($render)
        ->toContainElement(Selectors::ACTIONS . SupportSelectors::STANDALONE_ROOT . '#steps .sy-sc-actions-label', 'Next steps')
        ->toContainButtons(Selectors::ACTIONS . ' .sy-sc-actions-ctn ' . SupportSelectors::BUTTON, ['Save', 'Back']);
});

it('shows only its visible actions, and nothing at all when it is hidden or every action is', function (): void {
    $render = $this->renderSchemaComponent(Actions::make([Action::make('save')->hidden(), Action::make('cancel')])->label('Next steps'));

    expect($render)->toContainButtons(Selectors::ACTIONS . ' ' . SupportSelectors::BUTTON, ['Cancel'])
        ->and($this->renderSchemaComponent(Actions::make([Action::make('save')->hidden(), Action::make('cancel')->hidden()])->label('Next steps'))->filter(Selectors::ACTIONS))->toHaveCount(0)
        ->and($this->renderSchemaComponent(Actions::make([Action::make('cancel')])->label('Next steps')->hidden())->filter(Selectors::ACTIONS))->toHaveCount(0);
});

it('stretches its row across its width when told, over its alignment, and aligns it again when told not to', function (): void {
    expect($this->renderSchemaComponent(Actions::make([Action::make('save')])->alignment(Alignment::Center)->fullWidth()))
        ->toContainElement(Selectors::ACTIONS . ' .sy-sc-actions-ctn.sy-width-full' . SupportSelectors::NO_ALIGN, 'Save')
        ->and($this->renderSchemaComponent(Actions::make([Action::make('save')])->alignment(Alignment::Center)->fullWidth()->fullWidth(false)))
        ->toContainElement(Selectors::ACTIONS . ' .sy-sc-actions-ctn' . SupportSelectors::align('center') . ':not(.sy-width-full)', 'Save');
});

it('lets its schema run its actions by name, while it registers none of them itself', function (): void {
    $this->createLiveComponent(EmbeddedSchemaHost::class)
        ->callAction(TestAction::make('archive')->schemaComponent(schema: 'page'));

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['archive'])
        ->and(Actions::make([Action::make('archive')])->hasAction('archive'))->toBeFalse();
});
