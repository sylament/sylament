<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateCustomRowPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateGroupedRowPottedPlant;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

afterEach(function (): void {
    CreateArticle::alignFormActionsStart();
});

it('renders no button row for a page that declares none', function (): void {
    $page = $this->visit('/create-shapes/potted-plants/create-bare');

    expect($page->filter(Selectors::FORM . ' ' . Selectors::field('name')))->toHaveCount(1)
        ->and($page->filter(Selectors::FORM_ACTIONS))->toHaveCount(0);
});

it('binds an inline action and a grouped one to the page, dropping the group down at bottom-end', function (): void {
    $page = $this->visit('/create-shapes/potted-plants/create-grouped-row');

    expect($page)->toContainButtons(Selectors::FORM_ACTIONS . ' ' . ActionsSelectors::TRIGGER . ':not(' . SupportSelectors::DROPDOWN_ITEM . ')', ['Create', 'Repot'])
        ->and($page->filter(Selectors::FORM_ACTIONS . ' ' . SupportSelectors::dropdownValue('placement', 'bottom-end')))->toHaveCount(1)
        ->and($page)->toContainButtons(Selectors::FORM_ACTIONS . ' ' . SupportSelectors::DROPDOWN_ITEM, ['Water', 'Prune']);

    $tester = $this->createLiveComponent(CreateGroupedRowPottedPlant::class, ['panelId' => 'create-shapes'])->callAction('repot');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['repot']);

    $tester->callAction('prune');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['prune']);
});

it('mounts a row action back through the formActions schema its trigger names', function (): void {
    $page = $this->visit('/create-shapes/potted-plants/create-grouped-row');

    expect($page->filter(ActionsSelectors::trigger('repot') . ActionsSelectors::liveParameter('context', '{"schemaComponent":"formActions.form-actions"}')))->toHaveCount(1);

    $repot = TestAction::make('repot')->schemaComponent('form-actions', schema: 'formActions');

    $this->createLiveComponent(CreateGroupedRowPottedPlant::class, ['panelId' => 'create-shapes'])
        ->mountAction($repot)
        ->assertActionMounted($repot)
        ->callMountedAction();

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['repot']);
});

it('spans the row across the page in place of aligning it', function (): void {
    $page = $this->visit('/create-shapes/potted-plants/create-custom-row');

    expect($page->filter(Selectors::FORM_ACTIONS . ' .sy-width-full' . SupportSelectors::NO_ALIGN))->toHaveCount(1);
});

it('drives the form element and its submit button from one method name', function (): void {
    $page = $this->visit('/create-shapes/potted-plants/create-custom-row');

    expect($page->filter(Selectors::formSubmittingTo('plant')))->toHaveCount(1)
        ->and($page)->toContainElement(Selectors::FORM_SUBMIT_BUTTON, CreateCustomRowPottedPlant::CREATE_LABEL);

    $this->createLiveComponent(CreateCustomRowPottedPlant::class, ['panelId' => 'create-shapes'])
        ->fillForm(['name' => 'Fern'])
        ->call('plant')
        ->assertRedirect();

    expect($this->entityManager()->getRepository(Plant::class)->findAll())->sequence(fn ($plant) => $plant->toBeRecord(name: 'Fern'));
});

it('aligns the row of every page that keeps the static, by one call on any page class', function (): void {
    $this->seed($article = new Article('Draft'));

    expect($this->visit('/admin/articles/create')->filter(Selectors::FORM_ACTIONS . ' ' . SupportSelectors::align('start')))->toHaveCount(1);

    CreateArticle::alignFormActionsEnd();

    expect($this->visit('/admin/articles/create')->filter(Selectors::FORM_ACTIONS . ' ' . SupportSelectors::align('end')))->toHaveCount(1)
        ->and($this->visit("/admin/articles/{$article->id}/edit")->filter(Selectors::FORM_ACTIONS . ' ' . SupportSelectors::align('end')))->toHaveCount(1);

    CreateArticle::formActionsAlignment(Alignment::Center);

    expect($this->visit('/admin/articles/create')->filter(Selectors::FORM_ACTIONS . ' ' . SupportSelectors::align('center')))->toHaveCount(1);
});
