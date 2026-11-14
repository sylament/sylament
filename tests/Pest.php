<?php

declare(strict_types=1);

use Sylament\Tests\TestCase;

pest()->extend(TestCase::class)->in('.');

pest()->beforeEach(function (): void {
    $this::getContainer();
})->in('Packages');
