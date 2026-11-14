<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\TestFiles\FreeFunctions;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;

it('reads the functions a file declares outside any class', function (string $source, array $names): void {
    expect(FreeFunctions::declaredIn("<?php\n" . $source))->toBe($names);
})->with([
    'a function' => ['function a() {}', ['a']],
    'a function returning by reference' => ['function &a() {}', ['a']],
    'a method' => ['class A { function b() {} }', []],
    'a closure' => ['$a = function () {};', []],
    'a function import' => ['use function b;', []],
    'a method after a brace in an interpolated string' => ['$a = new class { function b($x) { return "{$x}}"; } function c() {} };', []],
    'a function after a class name constant' => ['$a = A::class; if (true) { function a() {} }', ['a']],
    'a function after a class: named argument' => ['f(class: 1); if (true) { function a() {} }', ['a']],
]);

it('finds a function a test file declares, and only in a test file', function (): void {
    $directory = WorkerDirectory::path() . '/' . uniqid('free-functions-', true);

    try {
        new Filesystem()->dumpFile($directory . '/Area/HelperTest.php', "<?php\n\nfunction plantedHelper(): int\n{\n    return 1;\n}\n");
        new Filesystem()->dumpFile($directory . '/Area/Helper.php', "<?php\n\nfunction plantedHelper(): int\n{\n    return 1;\n}\n");

        expect(FreeFunctions::inTestFiles($directory))->toBe(['Area/HelperTest.php: plantedHelper()']);
    } finally {
        new Filesystem()->remove($directory);
    }
});

it('declares no function in a test file', function (): void {
    $declared = FreeFunctions::inTestFiles(dirname(__DIR__));

    expect($declared)->toBe([], "A test file declares a function, which a worker that never loaded the file lacks under --parallel; make it a static method of a class under tests/Architecture/ or a TestCase method (.claude/rules/tests.md):\n" . implode("\n", $declared));
});
