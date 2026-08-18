<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;

class Hidden extends Field
{
    protected string|Closure|null $template = '@SylamentForms/partials/Hidden/index.html.twig';

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpan(['default' => 'hidden']);
    }
}
