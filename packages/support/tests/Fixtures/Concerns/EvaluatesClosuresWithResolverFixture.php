<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Concerns;

use Sylament\Support\Concerns\EvaluatesClosures;
use Sylament\Support\Data\ClosureParameter;

class EvaluatesClosuresWithResolverFixture
{
    use EvaluatesClosures;

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'subject' => ClosureParameter::make('from-subclass'),
            default => null,
        };
    }
}
