<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;

/**
 * A host a test builds with `new` and binds an action to, which sends the action named
 * `orders` to `/orders` when it has no URL of its own, and any other action nowhere.
 */
class DefaultUrlHost implements InteractsWithActions
{
    use HasActions;

    public function getDefaultActionUrl(Action $action): ?string
    {
        return $action->getName() === 'orders' ? '/orders' : null;
    }
}
