<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;

/**
 * A table host configuring nothing, built with `new` for a table to mount a summarizer's
 * column on outside any render of its own.
 */
class SummarizerMountHost implements InteractsWithTable
{
    use HasTable;
}
