<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Evaluation\Invoker;

it('fills a closure parameter from the first source that has it', function (Closure $closure, array $named, array $typed, ?Closure $resolve, mixed $result): void {
    expect($this::getContainer()->get(Invoker::class)->call($closure, $named, $typed, $resolve))->toBe($result);
})->with([
    'a named injection, over a typed one' => [
        fn (DateTimeImmutable $date): string => $date->format('Y'),
        ['date' => new DateTimeImmutable('2001-01-01')],
        [DateTimeImmutable::class => new DateTimeImmutable('2002-01-01')],
        null,
        '2001',
    ],
    'a named injection that is a closure, called' => [fn (string $value): string => $value, ['value' => fn (): string => 'called'], [], null, 'called'],
    'a typed injection, over the resolver' => [
        fn (DateTimeImmutable $date): string => $date->format('Y'),
        [],
        [DateTimeImmutable::class => new DateTimeImmutable('2002-01-01')],
        fn (): ClosureParameter => ClosureParameter::make(new DateTimeImmutable('2003-01-01')),
        '2002',
    ],
    'the resolver, a resolved null over the default' => [fn (?string $value = 'default'): string => $value ?? 'resolved null', [], [], fn (): ClosureParameter => ClosureParameter::make(null), 'resolved null'],
    'the container, for a type it has' => [fn (StaticInstanceRegistry $registry): string => $registry::class, [], [], null, StaticInstanceRegistry::class],
    'the default, for a class the container lacks' => [fn (DateTimeImmutable $date = new DateTimeImmutable('2004-01-01')): string => $date->format('Y'), [], [], null, '2004'],
    'null, for a nullable class the container lacks' => [fn (?DateTimeImmutable $date): string => $date?->format('Y') ?? 'null', [], [], null, 'null'],
    'nothing, for a variadic without an explicit injection' => [fn (string ...$values): string => implode(',', $values) ?: 'nothing', [], [], null, 'nothing'],
    'an explicit injection, for a variadic' => [fn (string ...$values): string => implode(',', $values), ['values' => 'given'], [], null, 'given'],
]);

it('throws naming the parameter no source fills, and the class the closure was evaluated for', function (): void {
    $invoker = $this::getContainer()->get(Invoker::class);
    $closure = fn (DateTimeImmutable $date): DateTimeImmutable => $date;

    expect(fn (): mixed => $invoker->call($closure))->toThrow(RuntimeException::class, 'Cannot resolve parameter DateTimeImmutable $date')
        ->and(fn (): mixed => $invoker->call($closure, context: Html::class))
        ->toThrow(RuntimeException::class, 'An attempt was made to evaluate a closure for [Sylament\Schemas\Components\Html], but [$date] was unresolvable.');
});
