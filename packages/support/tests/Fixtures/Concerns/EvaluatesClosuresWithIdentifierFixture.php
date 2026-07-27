<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Concerns;

use Sylament\Support\Concerns\EvaluatesClosures;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Tests\Fixtures\Evaluation\ServiceFixture;

class EvaluatesClosuresWithIdentifierFixture
{
    use EvaluatesClosures;

    public readonly ServiceFixture $namedService;

    public readonly ServiceFixture $typedService;

    public function __construct()
    {
        $this->namedService = new ServiceFixture();
        $this->typedService = new ServiceFixture();
        $this->evaluationIdentifier = 'fixture';
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'named' => ClosureParameter::make($this->namedService),
            'resolvedNull' => ClosureParameter::make(null),
            default => null,
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): ?ClosureParameter
    {
        return match ($parameterType) {
            ServiceFixture::class => ClosureParameter::make($this->typedService),
            default => null,
        };
    }
}
