<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Support\Enums\Alignment;

trait InteractsWithHeaderActions
{
    /**
     * @var array<Action|ActionGroup>|null
     */
    protected ?array $cachedHeaderActions = null;

    protected ?Alignment $headerActionsAlignment = null;

    /**
     * The override hook a page fills — public where Filament's is protected,
     * because the page template reads the bound list and Twig only reaches
     * public members.
     *
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    /**
     * The header list every page template renders, each entry bound to this page
     * and registered under its own name, so an action built inline in the
     * declaration reaches the host hooks its trigger renders from.
     *
     * @return array<Action|ActionGroup>
     */
    public function getCachedHeaderActions(): array
    {
        if ($this->cachedHeaderActions !== null) {
            return $this->cachedHeaderActions;
        }

        $this->cachedHeaderActions = [];
        $headerActions = $this->getHeaderActions();

        foreach ($headerActions as $headerAction) {
            if ($headerAction instanceof ActionGroup) {
                $headerAction->liveComponent($this);

                if ($headerAction->getDropdownPlacement() === null) {
                    $headerAction->dropdownPlacement('bottom-end');
                }

                foreach ($headerAction->getFlatActions() as $groupedAction) {
                    $this->cacheAction($groupedAction);
                }

                continue;
            }

            $this->cacheAction($headerAction);
        }

        return $this->cachedHeaderActions = $headerActions;
    }

    public function getHeaderActionsAlignment(): ?Alignment
    {
        return $this->headerActionsAlignment;
    }
}
