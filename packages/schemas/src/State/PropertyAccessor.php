<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Sylament\Support\DependencyInjection\HasStaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reads and writes a host component's state bag by absolute dotted state path.
 *
 * The dotted path is converted to a Symfony property path whose first segment is
 * the bag property (`data`) and whose remaining segments are array indices —
 * `data.address.city` becomes `data[address][city]` — mirroring UX Live
 * Components' own `adjustPropertyPathForData`. The configured framework accessor
 * is null-tolerant on missing indices (read) and auto-vivifies nested arrays
 * (write), so callers never guard for absent keys.
 */
final class PropertyAccessor implements StaticInstanceInterface
{
    use HasStaticInstance;

    public function __construct(
        private readonly PropertyAccessorInterface $accessor,
    ) {
        //
    }

    public function get(object $host, string $path): mixed
    {
        return $this->accessor->getValue($host, $this->toPropertyPath($path));
    }

    public function set(object $host, string $path, mixed $value): void
    {
        $this->accessor->setValue($host, $this->toPropertyPath($path), $value);
    }

    private function toPropertyPath(string $dottedPath): string
    {
        $segments = explode('.', $dottedPath);
        $first = array_shift($segments);

        if ($segments === []) {
            return $first;
        }

        return $first . '[' . implode('][', $segments) . ']';
    }
}
