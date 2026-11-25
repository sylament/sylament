<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

trait CanBeCopied
{
    protected bool|Closure $isCopyable = false;

    protected string|Closure|null $copyableState = null;

    protected string|TranslatableInterface|Closure|null $copyMessage = null;

    protected int|Closure|null $copyMessageDuration = null;

    public function copyable(bool|Closure $condition = true): static
    {
        $this->isCopyable = $condition;

        return $this;
    }

    public function copyableState(string|Closure|null $state): static
    {
        $this->copyableState = $state;

        return $this;
    }

    public function copyMessage(string|TranslatableInterface|Closure|null $message): static
    {
        $this->copyMessage = $message;

        return $this;
    }

    public function copyMessageDuration(int|Closure|null $duration): static
    {
        $this->copyMessageDuration = $duration;

        return $this;
    }

    public function isCopyable(mixed $state): bool
    {
        return $this->evaluate($this->isCopyable, [
            'state' => $state,
        ]);
    }

    public function getCopyableState(mixed $state): ?string
    {
        return $this->evaluate($this->copyableState, [
            'state' => $state,
        ]);
    }

    public function getCopyMessage(mixed $state): string
    {
        $message = $this->evaluate($this->copyMessage, [
            'state' => $state,
        ]) ?? new TranslatableMessage('components.copyable.messages.copied', domain: 'sylament');

        return (string) $this->translateText($message);
    }

    public function getCopyMessageDuration(mixed $state): int
    {
        return $this->evaluate($this->copyMessageDuration, [
            'state' => $state,
        ]) ?? 2000;
    }

    public function hasCopyable(): bool
    {
        return $this->isCopyable !== false;
    }
}
