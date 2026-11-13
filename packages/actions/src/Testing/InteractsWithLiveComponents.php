<?php

declare(strict_types=1);

namespace Sylament\Actions\Testing;

use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents as BaseInteractsWithLiveComponents;

/**
 * For a `KernelTestCase`, as UX's trait is.
 */
trait InteractsWithLiveComponents
{
    use BaseInteractsWithLiveComponents {
        createLiveComponent as createTestLiveComponent;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createLiveComponent(string $name, array $data = [], ?KernelBrowser $client = null): Testable
    {
        $client ??= self::getContainer()->get('test.client');

        if (!$client instanceof KernelBrowser) {
            throw new LogicException('The kernel has no [test.client]: turn framework.test on.');
        }

        return new Testable($this->createTestLiveComponent($name, $data, $client), $client);
    }
}
