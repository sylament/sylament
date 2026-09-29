<?php

declare(strict_types=1);

use Sylament\Support\Tests\Doctrine\TestPlatform;

beforeEach(function (): void {
    $this->previousUrl = getenv(TestPlatform::URL_VARIABLE);
});

afterEach(function (): void {
    putenv($this->previousUrl === false ? TestPlatform::URL_VARIABLE : TestPlatform::URL_VARIABLE . '=' . $this->previousUrl);
});

it('reads an unset or an empty url as in-memory SQLite', function (): void {
    putenv(TestPlatform::URL_VARIABLE);
    $unset = TestPlatform::current();

    putenv(TestPlatform::URL_VARIABLE . '=');

    expect($unset)->toBe(TestPlatform::Sqlite)
        ->and(TestPlatform::current())->toBe(TestPlatform::Sqlite)
        ->and(TestPlatform::url())->toBeNull();
});

it('names the server a mysql or a postgresql url points at', function (string $url, TestPlatform $platform): void {
    putenv(TestPlatform::URL_VARIABLE . '=' . $url);

    expect(TestPlatform::current())->toBe($platform)
        ->and(TestPlatform::url())->toBe($url);
})->with([
    'mysql' => ['mysql://root:secret@127.0.0.1:33307/sylament_test?serverVersion=8.4.0', TestPlatform::MySql],
    'postgresql' => ['postgresql://postgres:secret@127.0.0.1:55433/sylament_test?serverVersion=18', TestPlatform::PostgreSql],
    'pgsql' => ['pgsql://postgres:secret@127.0.0.1:55433/sylament_test', TestPlatform::PostgreSql],
]);

it('refuses a url naming any other scheme, and never echoes its credentials', function (string $url): void {
    putenv(TestPlatform::URL_VARIABLE . '=' . $url);

    $message = null;

    try {
        TestPlatform::current();
    } catch (LogicException $logicException) {
        $message = $logicException->getMessage();
    }

    expect($message)->toStartWith('SYLAMENT_TEST_DATABASE_URL must name a mysql:// or postgresql:// server')
        ->and($message)->not->toContain('secret');
})->with([
    'sqlite' => ['sqlite:///secret.db'],
    'oracle' => ['oracle://scott:secret@db/orcl'],
    'no scheme' => ['secret@127.0.0.1/sylament_test'],
]);
