<?php

declare(strict_types=1);

use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

beforeEach(function (): void {
    $this->previousUrl = getenv(TestPlatform::URL_VARIABLE);
});

afterEach(function (): void {
    putenv($this->previousUrl === false ? TestPlatform::URL_VARIABLE : TestPlatform::URL_VARIABLE . '=' . $this->previousUrl);
});

it('names the platform the url points at, and SQLite when it names none', function (?string $url, TestPlatform $platform, ?string $read): void {
    putenv($url === null ? TestPlatform::URL_VARIABLE : TestPlatform::URL_VARIABLE . '=' . $url);

    expect(TestPlatform::current())->toBe($platform)
        ->and(TestPlatform::url())->toBe($read);
})->with([
    'unset' => [null, TestPlatform::Sqlite, null],
    'empty' => ['', TestPlatform::Sqlite, null],
    'mysql' => ['mysql://root:secret@127.0.0.1:33307/sylament_test', TestPlatform::MySql, 'mysql://root:secret@127.0.0.1:33307/sylament_test'],
    'postgresql' => ['postgresql://postgres:secret@127.0.0.1:55433/sylament_test', TestPlatform::PostgreSql, 'postgresql://postgres:secret@127.0.0.1:55433/sylament_test'],
    'pgsql' => ['pgsql://postgres:secret@127.0.0.1:55433/sylament_test', TestPlatform::PostgreSql, 'pgsql://postgres:secret@127.0.0.1:55433/sylament_test'],
]);

it('refuses a url naming any other scheme without echoing its credentials', function (string $url): void {
    putenv(TestPlatform::URL_VARIABLE . '=' . $url);

    expect(TestPlatform::current(...))->toThrow(function (LogicException $exception): void {
        expect($exception->getMessage())->toStartWith('SYLAMENT_TEST_DATABASE_URL must name a mysql:// or postgresql:// server')
            ->not->toContain('secret');
    });
})->with([
    'sqlite' => ['sqlite:///secret.db'],
    'oracle' => ['oracle://scott:secret@db/orcl'],
    'no scheme' => ['secret@127.0.0.1/sylament_test'],
]);
