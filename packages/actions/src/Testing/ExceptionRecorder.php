<?php

declare(strict_types=1);

namespace Sylament\Actions\Testing;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Throwable;

/**
 * Keeps the first exception a request threw, so a tester that lets the kernel catch
 * exceptions can still rethrow the original.
 */
final class ExceptionRecorder
{
    private ?Throwable $throwable = null;

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->throwable ??= $event->getThrowable();
    }

    public function takeThrowable(): ?Throwable
    {
        $throwable = $this->throwable;
        $this->throwable = null;

        return $throwable;
    }
}
