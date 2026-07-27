<?php

declare(strict_types=1);

namespace Sylament\Support\Components;

use Sylament\Support\Concerns\Configurable;
use Sylament\Support\Concerns\EvaluatesClosures;

abstract class Component
{
    use Configurable;
    use EvaluatesClosures;
}
