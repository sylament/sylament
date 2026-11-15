<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\Html;

/**
 * An `Html` handing its template two variables of its own, one of which `templateData()` can override.
 */
class ExtraDataHtml extends Html
{
    public function getExtraTemplateData(): array
    {
        return [
            'source' => 'extra data',
            'extra' => 'extra data',
        ];
    }
}
