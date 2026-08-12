<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\EditAction;

/**
 * A host that answers the URL fallthrough the way a resource page will: it
 * keys off the action it is handed, so a test can prove the action instance
 * reaches the hook and that a host may decline for an action it has no page
 * for.
 */
class DefaultActionUrlHostFixture implements InteractsWithActions
{
    use HasActions;

    public function editAction(): Action
    {
        return EditAction::make();
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        if (!$action instanceof EditAction) {
            return null;
        }

        return '/hosted/' . $action->getName();
    }
}
