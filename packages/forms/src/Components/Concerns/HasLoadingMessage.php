<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

trait HasLoadingMessage
{
    protected string|TranslatableInterface|Closure|null $loadingMessage = null;

    public function loadingMessage(string|TranslatableInterface|Closure|null $message): static
    {
        $this->loadingMessage = $message;

        return $this;
    }

    public function getLoadingMessage(): string
    {
        return (string) $this->translateText($this->evaluate($this->loadingMessage) ?? new TranslatableMessage('components.select.loading_message', domain: 'sylament-forms'));
    }
}
