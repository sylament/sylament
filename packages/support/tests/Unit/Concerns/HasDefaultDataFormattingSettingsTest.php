<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Concerns;

use Sylament\Support\Tests\Fixtures\Concerns\HasDefaultDataFormattingSettingsFixture;

it("carries Filament's formatting defaults", function (): void {
    $fixture = new HasDefaultDataFormattingSettingsFixture();

    expect($fixture->getDefaultCurrency())->toBe('usd')
        ->and($fixture->getDefaultDateDisplayFormat())->toBe('M j, Y')
        ->and($fixture->getDefaultDateTimeDisplayFormat())->toBe('M j, Y H:i:s')
        ->and($fixture->getDefaultTimeDisplayFormat())->toBe('H:i:s')
        ->and($fixture->getDefaultNumberLocale())->toBeNull();
});

it('carries the three iso defaults no reader consumes yet', function (): void {
    $fixture = new HasDefaultDataFormattingSettingsFixture();

    expect($fixture->getDefaultIsoDateDisplayFormat())->toBe('L')
        ->and($fixture->getDefaultIsoDateTimeDisplayFormat())->toBe('LLL')
        ->and($fixture->getDefaultIsoTimeDisplayFormat())->toBe('LT');
});

it('returns a literal from every setter', function (): void {
    $fixture = new HasDefaultDataFormattingSettingsFixture()
        ->defaultCurrency('eur')
        ->defaultDateDisplayFormat('Y-m-d')
        ->defaultDateTimeDisplayFormat('Y-m-d H:i')
        ->defaultTimeDisplayFormat('H:i')
        ->defaultIsoDateDisplayFormat('l')
        ->defaultIsoDateTimeDisplayFormat('llll')
        ->defaultIsoTimeDisplayFormat('lt')
        ->defaultNumberLocale('de_DE');

    expect($fixture->getDefaultCurrency())->toBe('eur')
        ->and($fixture->getDefaultDateDisplayFormat())->toBe('Y-m-d')
        ->and($fixture->getDefaultDateTimeDisplayFormat())->toBe('Y-m-d H:i')
        ->and($fixture->getDefaultTimeDisplayFormat())->toBe('H:i')
        ->and($fixture->getDefaultIsoDateDisplayFormat())->toBe('l')
        ->and($fixture->getDefaultIsoDateTimeDisplayFormat())->toBe('llll')
        ->and($fixture->getDefaultIsoTimeDisplayFormat())->toBe('lt')
        ->and($fixture->getDefaultNumberLocale())->toBe('de_DE');
});

it('evaluates a closure from every setter', function (): void {
    $fixture = new HasDefaultDataFormattingSettingsFixture()
        ->defaultCurrency(fn (): string => 'gbp')
        ->defaultDateDisplayFormat(fn (): string => 'd/m/Y')
        ->defaultDateTimeDisplayFormat(fn (): string => 'd/m/Y H:i')
        ->defaultTimeDisplayFormat(fn (): string => 'H.i')
        ->defaultIsoDateDisplayFormat(fn (): string => 'LL')
        ->defaultIsoDateTimeDisplayFormat(fn (): string => 'LLLL')
        ->defaultIsoTimeDisplayFormat(fn (): string => 'LTS')
        ->defaultNumberLocale(fn (): string => 'fr_FR');

    expect($fixture->getDefaultCurrency())->toBe('gbp')
        ->and($fixture->getDefaultDateDisplayFormat())->toBe('d/m/Y')
        ->and($fixture->getDefaultDateTimeDisplayFormat())->toBe('d/m/Y H:i')
        ->and($fixture->getDefaultTimeDisplayFormat())->toBe('H.i')
        ->and($fixture->getDefaultIsoDateDisplayFormat())->toBe('LL')
        ->and($fixture->getDefaultIsoDateTimeDisplayFormat())->toBe('LLLL')
        ->and($fixture->getDefaultIsoTimeDisplayFormat())->toBe('LTS')
        ->and($fixture->getDefaultNumberLocale())->toBe('fr_FR');
});

it('takes a null number locale back', function (): void {
    expect(new HasDefaultDataFormattingSettingsFixture()->defaultNumberLocale('de_DE')->defaultNumberLocale(null)->getDefaultNumberLocale())->toBeNull();
});
