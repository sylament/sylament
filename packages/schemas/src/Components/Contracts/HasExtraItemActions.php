<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

use Sylament\Actions\Action;

/**
 * A component whose items each carry the same set of extra actions: the host
 * resolves them by name from the component's action cache, and an action mounted
 * with an `item` argument reads that item's state as its schema state.
 */
interface HasExtraItemActions
{
    /**
     * @return array<string, Action>
     */
    public function getExtraItemActions(): array;

    /**
     * @return array<string, mixed>
     */
    public function getItemState(string $key): array;
}
