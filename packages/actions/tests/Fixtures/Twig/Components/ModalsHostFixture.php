<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;

/**
 * A bare action host for the Modals unit tests: `HasActions` satisfies the whole
 * contract, so this only has to declare that it implements it — the stack walk
 * matches on the interface, not on any behavior.
 */
class ModalsHostFixture implements InteractsWithActions
{
    use HasActions;
}
