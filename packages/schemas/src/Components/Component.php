<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Sylament\Schemas\Components\Concerns\BelongsToContainer;
use Sylament\Schemas\Components\Concerns\CanBeDisabled;
use Sylament\Schemas\Components\Concerns\CanBeHidden;
use Sylament\Schemas\Components\Concerns\HasActions;
use Sylament\Schemas\Components\Concerns\HasChildComponents;
use Sylament\Schemas\Components\Concerns\HasState;
use Sylament\Schemas\Components\Concerns\HasStateBindingModifiers;
use Sylament\Schemas\Concerns\HasColumns;
use Sylament\Schemas\Concerns\HasGap;
use Sylament\Schemas\Components\Concerns\HasHeadings;
use Sylament\Schemas\Components\Concerns\HasId;
use Sylament\Schemas\Components\Concerns\HasKey;
use Sylament\Support\Components\Concerns\CanGrow;
use Sylament\Support\Components\Concerns\CanOrderColumns;
use Sylament\Support\Components\Concerns\CanSpanColumns;
use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Evaluation\ClosureParameter;

class Component extends ViewComponent
{
    use BelongsToContainer;
    use CanBeDisabled;
    use CanBeHidden;
    use CanGrow;
    use CanOrderColumns;
    use CanSpanColumns;
    use HasActions;
    use HasChildComponents;
    use HasColumns;
    use HasExtraAttributes;
    use HasGap;
    use HasHeadings;
    use HasId;
    use HasKey;
    use HasState;
    use HasStateBindingModifiers;

    protected string $evaluationIdentifier = 'component';

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'get' => ClosureParameter::make($this->makeGetUtility()),
            'set' => ClosureParameter::make($this->makeSetUtility()),
            'state' => ClosureParameter::make($this->getState()),
            'rawState' => ClosureParameter::make($this->getRawState()),
            'liveComponent' => ClosureParameter::make($this->getLiveComponent()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
