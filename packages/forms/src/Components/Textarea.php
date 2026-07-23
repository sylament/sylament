<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeLengthConstrained;

class Textarea extends Field
{
    use CanBeLengthConstrained;

    protected string|Closure|null $template = '@SylamentForms/components/Textarea.html.twig';

    protected int|Closure|null $rows = null;

    protected int|Closure|null $cols = null;

    public function rows(int|Closure|null $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function cols(int|Closure|null $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    public function getRows(): ?int
    {
        return $this->evaluate($this->rows);
    }

    public function getCols(): ?int
    {
        return $this->evaluate($this->cols);
    }
}
