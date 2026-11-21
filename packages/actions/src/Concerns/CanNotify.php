<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Notifications\Notification;
use Symfony\Contracts\Translation\TranslatableInterface;

use function Sylament\Support\blank;

trait CanNotify
{
    protected string|TranslatableInterface|Closure|null $successNotificationTitle = null;

    protected string|TranslatableInterface|Closure|null $failureNotificationTitle = null;

    protected Notification|Closure|null $successNotification = null;

    protected bool $isSuccessNotificationDisabled = false;

    protected Notification|Closure|null $failureNotification = null;

    protected bool $isFailureNotificationDisabled = false;

    public function successNotificationTitle(string|TranslatableInterface|Closure|null $title): static
    {
        $this->successNotificationTitle = $title;

        return $this;
    }

    public function failureNotificationTitle(string|TranslatableInterface|Closure|null $title): static
    {
        $this->failureNotificationTitle = $title;

        return $this;
    }

    public function successNotification(Notification|Closure|null $notification): static
    {
        $this->successNotification = $notification;
        $this->isSuccessNotificationDisabled = $notification === null;

        return $this;
    }

    public function failureNotification(Notification|Closure|null $notification): static
    {
        $this->failureNotification = $notification;
        $this->isFailureNotificationDisabled = $notification === null;

        return $this;
    }

    public function getSuccessNotificationTitle(): ?string
    {
        $title = $this->evaluate($this->successNotificationTitle);

        return $title instanceof TranslatableInterface ? $title->trans($this->getTranslator()) : $title;
    }

    public function getFailureNotificationTitle(): ?string
    {
        $title = $this->evaluate($this->failureNotificationTitle, $this->getFailureNotificationNamedInjections());

        return $title instanceof TranslatableInterface ? $title->trans($this->getTranslator()) : $title;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailureNotificationNamedInjections(): array
    {
        return [
            'failureCount' => $this->totalSelectedRecordsCount - $this->successfulSelectedRecordsCount,
            'isAll' => $this->successfulSelectedRecordsCount === 0,
            'successCount' => $this->successfulSelectedRecordsCount,
            'totalCount' => $this->totalSelectedRecordsCount,
        ];
    }

    public function sendSuccessNotification(): static
    {
        if ($this->isSuccessNotificationDisabled || !class_exists(Notification::class)) {
            return $this;
        }

        $notification = Notification::make()
            ->success()
            ->title($this->getSuccessNotificationTitle());

        $notification = $this->evaluate($this->successNotification, ['notification' => $notification]) ?? $notification;

        if (blank($notification->getTitle())) {
            return $this;
        }

        $notification->send();

        return $this;
    }

    public function sendFailureNotification(): static
    {
        if ($this->isFailureNotificationDisabled || !class_exists(Notification::class)) {
            return $this;
        }

        $notification = Notification::make();

        if ($this->canAccessSelectedRecords() && $this->successfulSelectedRecordsCount > 0) {
            $notification->warning();
        } else {
            $notification->danger();
        }

        $notification
            ->title($this->getFailureNotificationTitle())
            ->persistent();

        $notification = $this->evaluate($this->failureNotification, [
            ...$this->getFailureNotificationNamedInjections(),
            'notification' => $notification,
        ]) ?? $notification;

        if (blank($notification->getTitle())) {
            return $this;
        }

        $notification->send();

        return $this;
    }
}
