<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Closure;
use Sylament\Forms\Components\Field;

final class FixturePrefixedField extends Field
{
    protected string|Closure|null $template = '@FormsFixtures/partials/FixturePrefixedField/index.html.twig';

    private bool $rendersPrefix = true;

    private bool $rendersSuffix = false;

    public function rendersPrefix(bool $condition): static
    {
        $this->rendersPrefix = $condition;

        return $this;
    }

    public function rendersSuffix(bool $condition): static
    {
        $this->rendersSuffix = $condition;

        return $this;
    }

    public function shouldRenderPrefix(): bool
    {
        return $this->rendersPrefix;
    }

    public function shouldRenderSuffix(): bool
    {
        return $this->rendersSuffix;
    }
}
