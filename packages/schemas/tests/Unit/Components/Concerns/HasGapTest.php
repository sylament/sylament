<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('is not dense by default', function (): void {
    expect(Schema::make()->isDense())->toBeFalse();
});

it('is dense when inline', function (): void {
    expect(Schema::make()->inline()->isDense())->toBeTrue();
});

it('lets an explicit dense(false) beat the inline coupling', function (): void {
    expect(Schema::make()->inline()->dense(false)->isDense())->toBeFalse();
});
