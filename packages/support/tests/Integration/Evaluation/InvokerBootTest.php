<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration\Evaluation;

use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\Evaluation\Invoker;
use Sylament\Support\Tests\Fixtures\Components\ViewComponentFixture;

it('injects the container invoker into a component built through the resolver', function (): void {
    $resolver = $this::getContainer()->get(ComponentResolver::class);

    expect($resolver)->toBeInstanceOf(ComponentResolver::class);

    expect($resolver->instantiate(ViewComponentFixture::class)->getInvoker())
        ->toBe($this::getContainer()->get(Invoker::class));
});

it('falls back to a container-less invoker for a component built with new', function (): void {
    $this::getContainer();

    expect(new ViewComponentFixture()->getInvoker())
        ->toBeInstanceOf(Invoker::class)
        ->not->toBe($this::getContainer()->get(Invoker::class));
});
