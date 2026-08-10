<?php

declare(strict_types=1);

namespace Sylament\Tables\Columns;

use Closure;
use Sylament\Tables\Columns\Concerns\CanFormatState;
use Sylament\Tables\Columns\Concerns\HasColor;
use Sylament\Tables\Columns\Concerns\HasDescription;
use Sylament\Tables\Columns\Concerns\HasIcon;
use Sylament\Tables\Columns\Concerns\HasIconColor;

class TextColumn extends Column
{
    use CanFormatState;
    use HasColor;
    use HasDescription;
    use HasIcon;
    use HasIconColor;

    protected string|Closure|null $template = '@SylamentTables/partials/TextColumn/index.html.twig';
}
