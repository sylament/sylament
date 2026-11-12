<?php

declare(strict_types=1);

namespace Sylament\Tests;

use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

abstract class TestCase extends KernelTestCase
{
    use InteractsWithTwigComponents;

    private static int $declaredClassCount = 0;

    /**
     * @var list<class-string<StaticInstance>>
     */
    private static array $staticInstanceClasses = [];

    protected function tearDown(): void
    {
        foreach ($this->staticInstanceClasses() as $class) {
            $class::unsetInstance();
        }

        $storage = WorkerDirectory::path() . '/storage';

        if (is_dir($storage)) {
            new Filesystem()->remove($storage);
        }

        parent::tearDown();
    }

    /**
     * @return list<class-string<StaticInstance>>
     */
    private function staticInstanceClasses(): array
    {
        $declared = get_declared_classes();

        if (count($declared) === self::$declaredClassCount) {
            return self::$staticInstanceClasses;
        }

        self::$declaredClassCount = count($declared);
        self::$staticInstanceClasses = [];

        foreach ($declared as $class) {
            if (is_subclass_of($class, StaticInstance::class)) {
                self::$staticInstanceClasses[] = $class;
            }
        }

        return self::$staticInstanceClasses;
    }
}
