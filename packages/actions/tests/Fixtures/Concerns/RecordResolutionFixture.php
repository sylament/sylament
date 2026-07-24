<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasActions;

class RecordResolutionFixture
{
    use HasActions;

    public function exposeMountedActionRecord(): mixed
    {
        return $this->getMountedActionRecord();
    }
}
