<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanBeMounted;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeMountedFixture
{
    use CanBeMounted;
    use EvaluatesClosures;

    public function __construct()
    {
        $this->evaluationIdentifier = 'action';
    }
}
