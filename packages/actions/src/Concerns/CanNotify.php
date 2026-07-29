<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

trait CanNotify
{
    protected string|Closure|null $successNotificationTitle = null;

    protected string|Closure|null $failureNotificationTitle = null;

    public function successNotificationTitle(string|Closure|null $title): static
    {
        $this->successNotificationTitle = $title;

        return $this;
    }

    public function failureNotificationTitle(string|Closure|null $title): static
    {
        $this->failureNotificationTitle = $title;

        return $this;
    }

    public function getSuccessNotificationTitle(): ?string
    {
        return $this->evaluate($this->successNotificationTitle);
    }

    public function getFailureNotificationTitle(): ?string
    {
        return $this->evaluate($this->failureNotificationTitle);
    }

    public function sendSuccessNotification(): static
    {
        return $this;
    }

    public function sendFailureNotification(): static
    {
        return $this;
    }
}
