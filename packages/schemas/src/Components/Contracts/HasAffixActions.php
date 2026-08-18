<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

use Sylament\Actions\Action;

interface HasAffixActions
{
    /**
     * Keyed by action name — the cache splice spreads the map, so integer keys
     * would be renumbered and lose the name lookup.
     *
     * @return array<string, Action>
     */
    public function getPrefixActions(): array;

    /**
     * Keyed by action name — the cache splice spreads the map, so integer keys
     * would be renumbered and lose the name lookup.
     *
     * @return array<string, Action>
     */
    public function getSuffixActions(): array;
}
