<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Concerns;

use Sylament\Support\Concerns\EvaluatesClosures;

class EvaluatesClosuresFixture
{
    use EvaluatesClosures;

    public function callEvaluationValueIsFunctionAndHasParameter(mixed $value, string $parameterName): bool
    {
        return $this->evaluationValueIsFunctionAndHasParameter($value, $parameterName);
    }
}
