<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

class TimePicker extends DateTimePicker
{
    public function hasDate(): bool
    {
        return false;
    }
}
