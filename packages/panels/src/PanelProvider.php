<?php

declare(strict_types=1);

namespace Sylament\Panels;

abstract class PanelProvider
{
    abstract public function panel(Panel $panel): Panel;
}
