<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Support\Contracts\HasLabel;

/**
 * A glaze whose label is a plain string holding markup, a script among it.
 */
enum GlazeFinish: string implements HasLabel
{
    case Matte = 'matte';

    public function getLabel(): string
    {
        return '<em>Matte</em> glaze<script>alert(1)</script>';
    }
}
