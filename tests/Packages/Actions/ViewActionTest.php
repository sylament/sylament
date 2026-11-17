<?php

declare(strict_types=1);

use Sylament\Actions\ActionGroup;
use Sylament\Actions\ViewAction;
use Sylament\Tests\Fixtures\Actions\RecordActionsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('reads "View" in gray, with an eye on its own and inside a dropdown', function (): void {
    $bare = $this->renderViewComponent(ViewAction::make());

    expect($bare)->toContainElement('button' . SupportSelectors::GRAY, 'eye-20-solidView')
        ->and($bare)->toContainElement('button svg', 'eye-20-solid')
        ->and($this->renderViewComponent(ActionGroup::make([ViewAction::make()])))->toContainElement('.sy-dropdown-item' . SupportSelectors::GRAY . ' svg', 'eye-20-solid');
});

it("fills a disabled form from the bound record, headed \"View\" and the record's title, with one Close button", function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $tester = $this->createLiveComponent(RecordActionsHost::class)
        ->call('mountAction', ['name' => 'view', 'context' => ['recordKey' => (string) $lamp->id]])
        ->assertActionDataSet(['name' => 'Lamp']);

    expect($tester->render()->crawler())->toContainElement(Selectors::MODAL_HEADING, 'View Lamp')
        ->toContainElement('dialog[open] input[name="name"][disabled]')
        ->toContainButtons(Selectors::MODAL_FOOTER_ACTIONS, ['Close']);
});

it('calls a mounted view to no effect', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $this->createLiveComponent(RecordActionsHost::class)
        ->call('mountAction', ['name' => 'view', 'context' => ['recordKey' => (string) $lamp->id]])
        ->callMountedAction()
        ->assertActionNotMounted()
        ->assertNotNotified();

    expect($this->reload($lamp))->toBeRecord(name: 'Lamp');
});

it("shows the schema its host's resolver answers when it declares none, disabled", function (): void {
    $this->seed($lamp = new Product('Lamp'));

    $tester = $this->createLiveComponent(RecordActionsHost::class)
        ->call('mountAction', ['name' => 'viewResolved', 'context' => ['recordKey' => (string) $lamp->id]])
        ->assertActionDataSet(['name' => 'Lamp']);

    expect($tester->render()->crawler())->toContainElement('dialog[open] input[name="name"][disabled]');
});

it('is headed by the bare verb when nothing names its record', function (): void {
    expect($this->createLiveComponent(RecordActionsHost::class)->mountAction('viewBare')->render()->crawler())
        ->toContainElement(Selectors::MODAL_HEADING, 'View');
});
