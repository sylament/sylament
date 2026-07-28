<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasId;
use Sylament\Actions\Concerns\HasName;

class HasIdFixture
{
    use HasId;
    use HasName;
}
