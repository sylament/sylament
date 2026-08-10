<?php

declare(strict_types=1);

namespace Sylament\Notifications\Tests;

use Sylament\Support\Tests\TestCase as SupportTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

abstract class TestCase extends SupportTestCase
{
    use InteractsWithTwigComponents;
}
