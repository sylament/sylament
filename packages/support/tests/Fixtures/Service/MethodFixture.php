<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Service;

class MethodFixture
{
    public function instanceMethod(string $value): string
    {
        return "instance:{$value}";
    }

    public static function staticMethod(string $value): string
    {
        return "static:{$value}";
    }

    public function selfTyped(self $other): string
    {
        return 'self:' . spl_object_id($other);
    }

    /**
     * @return array{0: self, 1: string}
     */
    public function asInstanceArrayCallable(): array
    {
        $callable = [$this];
        $callable[] = 'instanceMethod';

        return $callable;
    }

    /**
     * @return array{0: class-string, 1: string}
     */
    public static function asStaticArrayCallable(): array
    {
        $callable = [self::class];
        $callable[] = 'staticMethod';

        return $callable;
    }
}
