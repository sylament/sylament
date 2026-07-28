<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\SlideOverPosition;
use Twig\Markup;

use function Sylament\Support\filled;
use function Sylament\Support\wrap;

trait CanOpenModal
{
    protected bool|Closure|null $hasModal = null;

    protected bool|Closure|null $isModalHidden = null;

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $cachedExtraModalFooterActions;

    /**
     * @var array<Action|ActionGroup>|Closure
     */
    protected array|Closure $extraModalFooterActions = [];

    /**
     * @var array<string, Action>
     */
    protected array $cachedModalActions;

    /**
     * @var array<Action|Closure>
     */
    protected array $modalActions = [];

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $cachedModalFooterActions;

    /**
     * @var array<Action>|Closure|null
     */
    protected array|Closure|null $modalFooterActions = null;

    protected Action|bool|Closure|null $modalCancelAction = null;

    protected Action|bool|Closure|null $modalSubmitAction = null;

    protected string|Markup|Closure|null $modalHeading = null;

    protected string|Markup|Closure|null $modalDescription = null;

    protected Markup|Closure|null $modalContent = null;

    protected Markup|Closure|null $modalContentFooter = null;

    protected string|Closure|null $modalIcon = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $modalIconColor = null;

    protected string|Closure|null $modalSubmitActionLabel = null;

    protected string|Closure|null $modalCancelActionLabel = null;

    protected string|Closure|null $modalWidth = null;

    protected bool|Closure|null $hasModalCloseButton = null;

    protected bool|Closure|null $isModalAutofocused = null;

    protected bool|Closure|null $isModalClickThrough = null;

    protected bool|Closure|null $isModalClosedByClickingAway = null;

    protected bool|Closure|null $isModalClosedByEscaping = null;

    protected bool|Closure|null $isModalFooterSticky = null;

    protected bool|Closure|null $isModalHeaderSticky = null;

    protected bool|Closure $isModalSlideOver = false;

    protected SlideOverPosition|Closure|null $modalSlideOverPosition = null;

    protected Alignment|string|Closure|null $modalAlignment = null;

    protected Alignment|string|Closure|null $modalFooterActionsAlignment = null;

    public function modal(bool|Closure|null $condition = true): static
    {
        $this->hasModal = $condition;

        return $this;
    }

    public function modalHidden(bool|Closure|null $condition = true): static
    {
        $this->isModalHidden = $condition;

        return $this;
    }

    public function modalHeading(string|Markup|Closure|null $heading = null): static
    {
        $this->modalHeading = $heading;

        return $this;
    }

    public function modalDescription(string|Markup|Closure|null $description = null): static
    {
        $this->modalDescription = $description;

        return $this;
    }

    /**
     * @param array<Action>|Closure|null $actions
     */
    public function modalFooterActions(array|Closure|null $actions = null): static
    {
        $this->modalFooterActions = $actions;

        return $this;
    }

    /**
     * @param array<Action|ActionGroup>|Closure $actions
     */
    public function extraModalFooterActions(array|Closure $actions): static
    {
        $this->extraModalFooterActions = $actions;

        return $this;
    }

    /**
     * @param array<Action|Closure> $actions
     */
    public function registerModalActions(array $actions): static
    {
        $this->modalActions = [
            ...$this->modalActions,
            ...$actions,
        ];

        return $this;
    }

    public function modalSubmitAction(Action|bool|Closure|null $action = null): static
    {
        $this->modalSubmitAction = $action;

        return $this;
    }

    public function modalCancelAction(Action|bool|Closure|null $action = null): static
    {
        $this->modalCancelAction = $action;

        return $this;
    }

    public function modalContent(Markup|Closure|null $content = null): static
    {
        $this->modalContent = $content;

        return $this;
    }

    public function modalContentFooter(Markup|Closure|null $footer = null): static
    {
        $this->modalContentFooter = $footer;

        return $this;
    }

    public function modalIcon(string|Closure|null $icon = null): static
    {
        $this->modalIcon = $icon;

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function modalIconColor(string|array|Closure|null $color = null): static
    {
        $this->modalIconColor = $color;

        return $this;
    }

    public function modalSubmitActionLabel(string|Closure|null $label = null): static
    {
        $this->modalSubmitActionLabel = $label;

        return $this;
    }

    public function modalCancelActionLabel(string|Closure|null $label = null): static
    {
        $this->modalCancelActionLabel = $label;

        return $this;
    }

    public function modalWidth(string|Closure|null $width = null): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    public function slideOver(bool|Closure $condition = true): static
    {
        $this->isModalSlideOver = $condition;

        return $this;
    }

    public function slideOverPosition(SlideOverPosition|Closure|null $position = null): static
    {
        $this->modalSlideOverPosition = $position;

        return $this;
    }

    public function modalAlignment(Alignment|string|Closure|null $alignment = null): static
    {
        $this->modalAlignment = $alignment;

        return $this;
    }

    public function modalFooterActionsAlignment(Alignment|string|Closure|null $alignment = null): static
    {
        $this->modalFooterActionsAlignment = $alignment;

        return $this;
    }

    public function stickyModalHeader(bool|Closure $condition = true): static
    {
        $this->isModalHeaderSticky = $condition;

        return $this;
    }

    public function stickyModalFooter(bool|Closure $condition = true): static
    {
        $this->isModalFooterSticky = $condition;

        return $this;
    }

    public function modalCloseButton(bool|Closure|null $condition = true): static
    {
        $this->hasModalCloseButton = $condition;

        return $this;
    }

    public function closeModalByClickingAway(bool|Closure|null $condition = true): static
    {
        $this->isModalClosedByClickingAway = $condition;

        return $this;
    }

    public function closeModalByEscaping(bool|Closure|null $condition = true): static
    {
        $this->isModalClosedByEscaping = $condition;

        return $this;
    }

    public function modalAutofocus(bool|Closure|null $condition = true): static
    {
        $this->isModalAutofocused = $condition;

        return $this;
    }

    public function modalClickThrough(bool|Closure|null $condition = true): static
    {
        $this->isModalClickThrough = $condition;

        return $this;
    }

    public function getLiveComponentCallMountedActionName(): ?string
    {
        return null;
    }

    public function hasModal(): ?bool
    {
        return $this->evaluate($this->hasModal);
    }

    public function hasCustomModalPresence(): bool
    {
        return $this->hasModal !== null;
    }

    public function shouldOpenModal(?Closure $checkForSchemaUsing = null): bool
    {
        if (is_bool($hasModal = $this->hasModal())) {
            return $hasModal;
        }

        if ((bool) $this->evaluate($this->isModalHidden)) {
            return false;
        }

        if ($this->hasCustomModalHeading()) {
            return true;
        }

        if ($this->hasModalDescription()) {
            return true;
        }

        if ($this->hasModalContent()) {
            return true;
        }

        if ($this->hasModalContentFooter()) {
            return true;
        }

        return (bool) ($checkForSchemaUsing instanceof Closure ? $checkForSchemaUsing($this) : false);
    }

    public function getCustomModalHeading(): string|Markup|null
    {
        return $this->evaluate($this->modalHeading);
    }

    public function hasCustomModalHeading(): bool
    {
        return filled($this->getCustomModalHeading());
    }

    public function getModalHeading(): string|Markup|null
    {
        return $this->getCustomModalHeading() ?? $this->getLabel();
    }

    public function getModalDescription(): string|Markup|null
    {
        if (filled($description = $this->evaluate($this->modalDescription))) {
            return $description;
        }

        if ($this->isConfirmationRequired()) {
            return 'Are you sure you would like to do this?';
        }

        return null;
    }

    public function hasModalDescription(): bool
    {
        return filled($this->getModalDescription());
    }

    public function getModalContent(): ?Markup
    {
        return $this->evaluate($this->modalContent);
    }

    public function getModalContentFooter(): ?Markup
    {
        return $this->evaluate($this->modalContentFooter);
    }

    public function hasModalContent(): bool
    {
        return $this->modalContent !== null;
    }

    public function hasModalContentFooter(): bool
    {
        return $this->modalContentFooter !== null;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getModalFooterActions(): array
    {
        if (isset($this->cachedModalFooterActions)) {
            return $this->cachedModalFooterActions;
        }

        if ($this->modalFooterActions !== null) {
            $actions = [];

            foreach ($this->evaluate($this->modalFooterActions) as $modalAction) {
                $actions[$modalAction->getName()] = $this->prepareModalAction($modalAction);
            }

            return $this->cachedModalFooterActions = $actions;
        }

        $actions = [];

        if ($submitAction = $this->getModalSubmitAction()) {
            $actions['submit'] = $submitAction;
        }

        $actions = [
            ...$actions,
            ...$this->getExtraModalFooterActions(),
        ];

        if ($cancelAction = $this->getModalCancelAction()) {
            $actions['cancel'] = $cancelAction;
        }

        if (in_array($this->getModalFooterActionsAlignment(), [Alignment::Center, 'center'], true)) {
            $actions = array_reverse($actions);
        }

        return $this->cachedModalFooterActions = $actions;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getVisibleModalFooterActions(): array
    {
        return array_filter(
            $this->getModalFooterActions(),
            fn (Action|ActionGroup $action): bool => $action->isVisible(),
        );
    }

    public function getModalSubmitAction(): ?Action
    {
        $color = $this->getColor();

        $action = static::makeModalAction('submit')
            ->label($this->getModalSubmitActionLabel())
            ->action($this->getLiveComponentCallMountedActionName())
            ->color(match ($color) {
                'gray', null => 'primary',
                default => $color,
            });

        $action = $this->evaluate($this->modalSubmitAction, ['action' => $action]) ?? $action;

        return $action instanceof Action ? $action : null;
    }

    public function getModalCancelAction(): ?Action
    {
        $action = static::makeModalAction('cancel')
            ->label($this->getModalCancelActionLabel())
            ->close()
            ->color('gray');

        $action = $this->evaluate($this->modalCancelAction, ['action' => $action]) ?? $action;

        return $action instanceof Action ? $action : null;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getExtraModalFooterActions(): array
    {
        if (isset($this->cachedExtraModalFooterActions)) {
            return $this->cachedExtraModalFooterActions;
        }

        $actions = [];

        foreach ($this->evaluate($this->extraModalFooterActions) as $action) {
            if ($action instanceof ActionGroup) {
                $actions[] = $this->prepareModalActionGroup($action);
            } else {
                $actions[$action->getName()] = $this->prepareModalAction($action);
            }
        }

        return $this->cachedExtraModalFooterActions = $actions;
    }

    /**
     * @return array<string, Action>
     */
    public function getModalActions(): array
    {
        if (isset($this->cachedModalActions)) {
            return $this->cachedModalActions;
        }

        $actions = [];

        foreach ($this->getModalFooterActions() as $key => $action) {
            if ($action instanceof ActionGroup) {
                foreach ($action->getFlatActions() as $flatAction) {
                    $actions[$flatAction->getName()] = $flatAction;
                }
            } else {
                $actions[(string) $key] = $action;
            }
        }

        foreach ($this->modalActions as $action) {
            foreach (wrap($this->evaluate($action)) as $modalAction) {
                $actions[$modalAction->getName()] = $this->prepareModalAction($modalAction);
            }
        }

        return $this->cachedModalActions = $actions;
    }

    public function getModalAction(string $name): ?Action
    {
        return $this->getModalActions()[$name] ?? null;
    }

    public function prepareModalAction(Action $action): Action
    {
        $action
            ->parentAction($this instanceof Action ? $this : null)
            ->schemaContainer($this->getSchemaContainer())
            ->schemaComponent($this->getSchemaComponent())
            ->liveComponent($this->getLiveComponentOrNull());

        if (! $action->hasRecord()) {
            $action->record($this->getRecord());
        }

        return $action;
    }

    protected function prepareModalActionGroup(ActionGroup $group): ActionGroup
    {
        $group
            ->schemaContainer($this->getSchemaContainer())
            ->schemaComponent($this->getSchemaComponent())
            ->liveComponent($this->getLiveComponentOrNull());

        if (! $group->hasRecord()) {
            $group->record($this->getRecord());
        }

        foreach ($group->getActions() as $nestedAction) {
            if ($nestedAction instanceof ActionGroup) {
                $this->prepareModalActionGroup($nestedAction);

                continue;
            }

            $this->prepareModalAction($nestedAction);
        }

        return $group;
    }

    public function makeModalAction(string $name): Action
    {
        return Action::make($name)->button();
    }

    /**
     * @param array<string, mixed>|null $arguments
     */
    public function makeModalSubmitAction(string $name, ?array $arguments = null): Action
    {
        return $this->makeModalAction($name)
            ->callParent($this->getLiveComponentCallMountedActionName())
            ->arguments($arguments)
            ->color('gray');
    }

    public function getModalIcon(): ?string
    {
        if (filled($icon = $this->evaluate($this->modalIcon))) {
            return $icon;
        }

        if ($this->isConfirmationRequired()) {
            return 'heroicons:exclamation-triangle';
        }

        return null;
    }

    /**
     * @return string|array<string>|null
     */
    public function getModalIconColor(): string|array|null
    {
        return $this->evaluate($this->modalIconColor) ?? $this->getColor() ?? 'primary';
    }

    public function getModalSubmitActionLabel(): string
    {
        if (filled($label = $this->evaluate($this->modalSubmitActionLabel))) {
            return $label;
        }

        return $this->isConfirmationRequired() ? 'Confirm' : 'Submit';
    }

    public function getModalCancelActionLabel(): string
    {
        return $this->evaluate($this->modalCancelActionLabel) ?? 'Cancel';
    }

    public function getModalWidth(): string
    {
        return $this->evaluate($this->modalWidth) ?? 'md';
    }

    public function getModalAlignment(): Alignment|string
    {
        if ($alignment = $this->evaluate($this->modalAlignment)) {
            return $alignment;
        }

        if ($this->isConfirmationRequired() || in_array($this->getModalWidth(), ['xs', 'sm'], true)) {
            return Alignment::Center;
        }

        return Alignment::Start;
    }

    public function getModalFooterActionsAlignment(): Alignment|string|null
    {
        if ($alignment = $this->evaluate($this->modalFooterActionsAlignment)) {
            return $alignment;
        }

        if ($this->isConfirmationRequired()) {
            return Alignment::Center;
        }

        return null;
    }

    public function isModalSlideOver(): bool
    {
        return (bool) $this->evaluate($this->isModalSlideOver);
    }

    public function getModalSlideOverPosition(): ?SlideOverPosition
    {
        return $this->evaluate($this->modalSlideOverPosition);
    }

    public function isModalHeaderSticky(): bool
    {
        return (bool) ($this->evaluate($this->isModalHeaderSticky) ?? $this->isModalSlideOver());
    }

    public function isModalFooterSticky(): bool
    {
        return (bool) ($this->evaluate($this->isModalFooterSticky) ?? $this->isModalSlideOver());
    }

    public function hasModalCloseButton(): bool
    {
        return $this->evaluate($this->hasModalCloseButton) ?? true;
    }

    public function isModalClosedByClickingAway(): bool
    {
        return (bool) ($this->evaluate($this->isModalClosedByClickingAway) ?? true);
    }

    public function isModalClosedByEscaping(): bool
    {
        return (bool) ($this->evaluate($this->isModalClosedByEscaping) ?? true);
    }

    public function isModalAutofocused(): bool
    {
        return $this->evaluate($this->isModalAutofocused) ?? true;
    }

    public function isModalClickThrough(): bool
    {
        return (bool) $this->evaluate($this->isModalClickThrough);
    }
}
