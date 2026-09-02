<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Alignment;

trait InteractsWithFormActions
{
    public static string|Alignment $formActionsAlignment = Alignment::Start;

    /**
     * @var array<Action|ActionGroup>|null
     */
    protected ?array $cachedFormActions = null;

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * The form row bound and registered exactly as the header list is, so a
     * closure action declared inline reaches the host hooks its trigger renders
     * from.
     *
     * @return array<Action|ActionGroup>
     */
    public function getCachedFormActions(): array
    {
        if ($this->cachedFormActions !== null) {
            return $this->cachedFormActions;
        }

        $this->cachedFormActions = [];
        $formActions = $this->getFormActions();

        foreach ($formActions as $formAction) {
            if ($formAction instanceof ActionGroup) {
                $formAction->liveComponent($this);

                if ($formAction->getDropdownPlacement() === null) {
                    $formAction->dropdownPlacement('bottom-end');
                }

                foreach ($formAction->getFlatActions() as $groupedAction) {
                    $this->cacheAction($groupedAction);
                }

                continue;
            }

            $this->cacheAction($formAction);
        }

        return $this->cachedFormActions = $formActions;
    }

    /**
     * The actions row the form variant renders below the schema, through the
     * `formActions` schema: a keyed component needs a container to resolve its
     * id, and a mounted row action resolves back through that schema's name.
     */
    public function getFormActionsContentComponent(): Actions
    {
        return Actions::make($this->getCachedFormActions())
            ->alignment($this->getFormActionsAlignment())
            ->fullWidth($this->hasFullWidthFormActions())
            ->key('form-actions');
    }

    public function formActions(Schema $schema): Schema
    {
        return $schema->components([$this->getFormActionsContentComponent()]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    /**
     * The live action the form element submits to, and the one a submit action
     * names — one source for both.
     */
    public function getSubmitFormLiveComponentMethodName(): string
    {
        return 'save';
    }

    public function getFormActionsAlignment(): Alignment|string
    {
        return static::$formActionsAlignment;
    }

    public static function formActionsAlignment(string|Alignment $alignment): void
    {
        static::$formActionsAlignment = $alignment;
    }

    public static function alignFormActionsStart(): void
    {
        static::$formActionsAlignment = Alignment::Start;
    }

    public static function alignFormActionsCenter(): void
    {
        static::$formActionsAlignment = Alignment::Center;
    }

    public static function alignFormActionsEnd(): void
    {
        static::$formActionsAlignment = Alignment::End;
    }
}
