<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Notifications\Notification;

use function Sylament\Support\blank;

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
        $title = $this->getSuccessNotificationTitle();

        if (!class_exists(Notification::class) || blank($title)) {
            return $this;
        }

        Notification::make()
            ->success()
            ->title($title)
            ->send();

        return $this;
    }

    public function sendFailureNotification(): static
    {
        $title = $this->getFailureNotificationTitle();

        if (!class_exists(Notification::class) || blank($title)) {
            return $this;
        }

        Notification::make()
            ->danger()
            ->title($title)
            ->persistent()
            ->send();

        return $this;
    }
}
