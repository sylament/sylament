<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasAffixActions;

/**
 * Stands in for a forms field carrying prefix/suffix actions, so the
 * `cacheActions()` affix splice can be exercised without the `forms` concern.
 */
class AffixActionField extends Component implements HasAffixActions
{
    /**
     * @var array<string, Action>
     */
    protected array $prefixActions = [];

    /**
     * @var array<string, Action>
     */
    protected array $suffixActions = [];

    /**
     * @param array<string, Action> $actions
     */
    public function prefixActions(array $actions): static
    {
        $this->prefixActions = $actions;

        return $this;
    }

    /**
     * @param array<string, Action> $actions
     */
    public function suffixActions(array $actions): static
    {
        $this->suffixActions = $actions;

        return $this;
    }

    public function getPrefixActions(): array
    {
        return $this->prefixActions;
    }

    public function getSuffixActions(): array
    {
        return $this->suffixActions;
    }
}
