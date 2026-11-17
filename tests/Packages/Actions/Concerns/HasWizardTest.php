<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Actions\StepwiseActionHost;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('opens its steps as a wizard on the first step, or on the step it starts on, every step reachable when skippable', function (): void {
    $wizard = fn (string $name): Crawler => $this->createLiveComponent(StepwiseActionHost::class)->mountAction($name)->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN . ' ' . SchemasSelectors::WIZARD);

    expect($wizard('onboard'))->toContainElement(SchemasSelectors::wizardValue('start-step', '1') . ':not(' . SchemasSelectors::wizardValue('skippable') . ')')
        ->toContainButtons(SchemasSelectors::WIZARD_STEP_BUTTON, ['check 01 Account', 'check 02 Review Not completed'])
        ->and($wizard('resume'))->toContainElement(SchemasSelectors::wizardValue('start-step', '2') . SchemasSelectors::wizardValue('skippable', 'true'));
});

it('makes a wizard of the steps schema() declares once it modifies the wizard, with no modal footer under it', function (): void {
    $modal = $this->createLiveComponent(StepwiseActionHost::class)->mountAction('retouched')->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

    expect($modal)->toContainElement(SchemasSelectors::WIZARD . ' ' . SchemasSelectors::WIZARD_STEP_BUTTON, 'check 01 Account')
        ->and($modal->filter(SupportSelectors::MODAL_FOOTER)->count())->toBe(0);
});
