<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components;

use Sylament\Support\Components\ViewComponent;

class ViewComponentWithExtraTemplateDataFixture extends ViewComponent
{
    public function getExtraTemplateData(): array
    {
        return [
            'name' => 'extra',
            'source' => 'extra',
        ];
    }
}
