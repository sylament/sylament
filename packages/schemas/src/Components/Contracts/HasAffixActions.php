<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

use Sylament\Actions\Action;

interface HasAffixActions
{
    /**
     * @return array<string, Action>
     */
    public function getPrefixActions(): array;

    /**
     * @return array<string, Action>
     */
    public function getSuffixActions(): array;
}
