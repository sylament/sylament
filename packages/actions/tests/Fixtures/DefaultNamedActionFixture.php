<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures;

use Sylament\Actions\Action;

final class DefaultNamedActionFixture extends Action
{
    public static function getDefaultName(): string
    {
        return 'publish';
    }
}
