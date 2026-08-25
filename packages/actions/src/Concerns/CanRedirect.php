<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

use function Sylament\Support\filled;

trait CanRedirect
{
    protected string|Closure|null $failureRedirectUrl = null;

    protected string|Closure|null $successRedirectUrl = null;

    /**
     * The URL the pipeline turns into a response once the action has run — null
     * until a dispatch or an explicit redirect() resolves one.
     */
    protected ?string $pendingRedirectUrl = null;

    public function successRedirectUrl(string|Closure|null $url): static
    {
        $this->successRedirectUrl = $url;

        return $this;
    }

    public function failureRedirectUrl(string|Closure|null $url): static
    {
        $this->failureRedirectUrl = $url;

        return $this;
    }

    public function dispatchSuccessRedirect(): static
    {
        $url = $this->evaluate($this->successRedirectUrl)
            ?? $this->getLiveComponentOrNull()?->getDefaultActionSuccessRedirectUrl($this);

        if (filled($url)) {
            $this->redirect($url);
        }

        return $this;
    }

    public function dispatchFailureRedirect(): static
    {
        $url = $this->evaluate($this->failureRedirectUrl)
            ?? $this->getLiveComponentOrNull()?->getDefaultActionFailureRedirectUrl($this);

        if (filled($url)) {
            $this->redirect($url);
        }

        return $this;
    }

    public function redirect(string|Closure $url): void
    {
        $this->pendingRedirectUrl = $this->evaluate($url);
    }

    public function getPendingRedirectUrl(): ?string
    {
        return $this->pendingRedirectUrl;
    }

    public function resetPendingRedirect(): void
    {
        $this->pendingRedirectUrl = null;
    }
}
