<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

trait HasFieldWrapper
{
    protected ?string $fieldWrapperView = null;

    public function fieldWrapperView(?string $view): static
    {
        $this->fieldWrapperView = $view;

        return $this;
    }

    public function getFieldWrapperView(): ?string
    {
        return $this->fieldWrapperView;
    }
}
