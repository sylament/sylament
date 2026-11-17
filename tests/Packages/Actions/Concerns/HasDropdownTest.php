<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('opens its dropdown where it is placed, a placement over a default one, below and to the start with neither', function (Closure $group, string $placement): void {
    expect($this->renderViewComponent($group()))->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('placement', $placement));
})->with([
    'a placement' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->dropdownPlacement('top-end'), 'top-end'],
    'a placement over a default one' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->defaultDropdownPlacement('left-start')->dropdownPlacement('top-end'), 'top-end'],
    'a default one' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->defaultDropdownPlacement('left-start'), 'left-start'],
    'neither' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')]), 'bottom-start'],
]);

it('opens its dropdown at the offset it is given and flips it unless told not to, 8 pixels away by default', function (): void {
    expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->dropdownOffset(4)->dropdownFlip(false)))
        ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('offset', '4') . SupportSelectors::dropdownValue('flip', 'false'))
        ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])))
        ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('offset', '8') . SupportSelectors::dropdownValue('flip', 'true'));
});

it('sizes its panel by the width and the maximum height it is given, scrolling past that height, and leaves it to the stylesheet otherwise', function (): void {
    $sized = $this->renderViewComponent(ActionGroup::make([Action::make('edit')])->dropdownWidth('20rem')->dropdownMaxHeight('10rem'));
    $plain = $this->renderViewComponent(ActionGroup::make([Action::make('edit')]));

    expect($sized)->toContainElement(SupportSelectors::DROPDOWN_PANEL . SupportSelectors::dropdownPanelStyle('width', '20rem') . SupportSelectors::dropdownPanelStyle('max-height', '10rem') . SupportSelectors::dropdownPanelStyle('overflow', 'auto'))
        ->and($plain)->toContainElement(SupportSelectors::DROPDOWN_PANEL . ':not([style])');
});

it('renders its members in an inline row instead of a dropdown when told', function (): void {
    $inline = $this->renderViewComponent(ActionGroup::make([Action::make('edit')])->dropdown(false));

    expect($inline)->toContainElement(Selectors::INLINE_GROUP, 'Edit')
        ->and($inline->filter(SupportSelectors::DROPDOWN))->toHaveCount(0)
        ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])))->toContainElement(SupportSelectors::DROPDOWN . ' ' . SupportSelectors::DROPDOWN_ITEM, 'Edit');
});

it("teleports its dropdown when told, its own choice over a host's default, and the default when it makes none", function (Closure $group, string $dropdown): void {
    expect($this->renderViewComponent($group()))->toContainElement(SupportSelectors::DROPDOWN . $dropdown);
})->with([
    'told' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->dropdownTeleport(), SupportSelectors::TELEPORTED_DROPDOWN],
    "a host's default" => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->defaultDropdownTeleport(), SupportSelectors::TELEPORTED_DROPDOWN],
    "told not to, over a host's default" => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')])->defaultDropdownTeleport()->dropdownTeleport(false), SupportSelectors::KEPT_DROPDOWN],
    'neither' => [fn (): ActionGroup => ActionGroup::make([Action::make('edit')]), SupportSelectors::KEPT_DROPDOWN],
]);
