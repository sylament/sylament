<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Pages\Page;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Alignment;

/**
 * Declares its form row the way a record page does, and counts the reads so the
 * walk's memoisation can be observed.
 */
final class FormActionsProbePage extends Page
{
    public int $reads = 0;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        $this->reads++;

        return [
            Action::make('save'),
            ActionGroup::make([Action::make('discard')]),
        ];
    }
}

afterEach(function (): void {
    Page::alignFormActionsStart();
});

it('defaults the form row to nothing, aligned at the start and not full width', function (): void {
    $page = new class extends Page {};

    expect($page->getCachedFormActions())->toBe([])
        ->and($page->getFormActionsAlignment())->toBe(Alignment::Start)
        ->and($page->getSubmitFormLiveComponentMethodName())->toBe('save');
});

it('binds every form action to the page and registers it under its own name', function (): void {
    $page = new FormActionsProbePage();

    [$save, $group] = $page->getCachedFormActions();
    $grouped = $group->getFlatActions()['discard'];

    expect($save->getLiveComponent())->toBe($page)
        ->and($group->getLiveComponent())->toBe($page)
        ->and($grouped->getLiveComponent())->toBe($page)
        ->and($group->getDropdownPlacement())->toBe('bottom-end')
        ->and($page->getAction('save'))->toBe($save)
        ->and($page->getAction('discard'))->toBe($grouped);
});

it('reads the declared form list once, however it is reached', function (): void {
    $page = new FormActionsProbePage();

    $page->getCachedFormActions();
    $page->getCachedFormActions();

    expect($page->getAction('nothing'))->toBeNull()
        ->and($page->reads)->toBe(1);
});

it('hangs the cached row under one keyed Actions component', function (): void {
    $page = new FormActionsProbePage();

    $component = $page->getFormActionsContentComponent();

    expect($component)->toBeInstanceOf(Actions::class)
        ->and($component->getKey(isAbsolute: false))->toBe('form-actions')
        ->and($component->getChildSchema()?->getComponents())->toBe($page->getCachedFormActions())
        ->and($component->getAlignment())->toBe(Alignment::Start)
        ->and($component->isFullWidth())->toBeFalse();
});

it('wraps that one component in the schema the named page schema is given', function (): void {
    $page = new FormActionsProbePage();
    $schema = Schema::make();

    $returned = $page->formActions($schema);
    $components = $returned->getComponents();

    expect($returned)->toBe($schema)
        ->and($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Actions::class);
});

it('carries the alignment static across every page and takes it back', function (): void {
    Page::alignFormActionsEnd();

    expect(new FormActionsProbePage()->getFormActionsAlignment())->toBe(Alignment::End);

    Page::alignFormActionsCenter();

    expect(new FormActionsProbePage()->getFormActionsAlignment())->toBe(Alignment::Center);

    Page::formActionsAlignment('justify');

    expect(new FormActionsProbePage()->getFormActionsAlignment())->toBe('justify');
});
