<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Pages\Page;
use Sylament\Support\Enums\Alignment;

/**
 * Declares its header list the way a user page does — nothing bound by hand —
 * and counts the reads so the walk's memoisation can be observed.
 */
final class HeaderActionsProbePage extends Page
{
    public int $reads = 0;

    protected ?Alignment $headerActionsAlignment = Alignment::End;

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        $this->reads++;

        return [
            Action::make('ping'),
            ActionGroup::make([Action::make('archive')]),
            ActionGroup::make([Action::make('restore')])->dropdownPlacement('top-start'),
        ];
    }
}

it('defaults the header list, its alignment and the cached walk to nothing', function (): void {
    $page = new class extends Page {};

    expect($page->getHeaderActions())->toBe([])
        ->and($page->getCachedHeaderActions())->toBe([])
        ->and($page->getHeaderActionsAlignment())->toBeNull();
});

it('binds every header action to the page and registers it under its own name', function (): void {
    $page = new HeaderActionsProbePage();

    [$ping, $group] = $page->getCachedHeaderActions();
    $grouped = $group->getFlatActions()['archive'];

    expect($ping->getLiveComponent())->toBe($page)
        ->and($group->getLiveComponent())->toBe($page)
        ->and($grouped->getLiveComponent())->toBe($page)
        ->and($page->getAction('ping'))->toBe($ping)
        ->and($page->getAction('archive'))->toBe($grouped);
});

it('reads the declared header list once, however it is reached', function (): void {
    $page = new HeaderActionsProbePage();

    $page->getCachedHeaderActions();
    $page->getCachedHeaderActions();

    expect($page->getAction('nothing'))->toBeNull()
        ->and($page->reads)->toBe(1);
});

it('opens a placement-less header group at the end of the trigger and leaves a declared one alone', function (): void {
    $page = new HeaderActionsProbePage();

    [, $defaulted, $declared] = $page->getCachedHeaderActions();

    expect($defaulted->getDropdownPlacement())->toBe('bottom-end')
        ->and($declared->getDropdownPlacement())->toBe('top-start');
});

it('answers the alignment the page names', function (): void {
    expect(new HeaderActionsProbePage()->getHeaderActionsAlignment())->toBe(Alignment::End);
});
