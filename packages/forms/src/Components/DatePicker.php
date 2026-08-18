<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

class DatePicker extends DateTimePicker
{
    public function hasTime(): bool
    {
        return false;
    }
}
