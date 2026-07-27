<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasDropdown;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasDropdownFixture
{
    use EvaluatesClosures;
    use HasDropdown;
}
