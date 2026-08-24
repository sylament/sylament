<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

function fixtureUpload(string $name = 'hello.txt', string $contents = 'hello'): UploadedFile
{
    $path = (string) tempnam(sys_get_temp_dir(), 'syl');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, 'text/plain', test: true);
}

/**
 * @param array<string, UploadedFile> $files
 *
 * @return array{TestLiveComponent, ?array{event: non-empty-string, payload: array<string, mixed>}}
 */
function uploadToLiveStateHost(string $componentKey, string $fileKey, array $files): array
{
    $component = test()->createLiveComponent('LiveStateHost');
    $component->call(
        'uploadSchemaComponentFile',
        ['componentKey' => $componentKey, 'fileKey' => $fileKey],
        $files,
    );

    return [
        $component,
        $component->getDispatchedBrowserEvent($component->render(), 'sylament:schema-component-method-result'),
    ];
}

/**
 * @return array{TestLiveComponent, ?array{event: non-empty-string, payload: array<string, mixed>}}
 */
function uploadOneToLiveStateHost(string $componentKey, string $fileKey): array
{
    return uploadToLiveStateHost($componentKey, $fileKey, ['sylament_upload_' . $fileKey => fixtureUpload()]);
}

it('hands the multipart part under the file key to an AcceptsUploadedFiles component and answers the result', function (): void {
    [$component, $event] = uploadOneToLiveStateHost('schema.upload', 'abc-123');

    expect($event)->not->toBeNull()
        ->and($event['payload'])->toBe([
            'componentKey' => 'schema.upload',
            'method' => 'uploadSchemaComponentFile',
            'fileKey' => 'abc-123',
            'result' => ['ok' => true, 'fileKey' => 'abc-123'],
        ])
        ->and($component->component()->data['upload'])->toBe([
            'fileKey' => 'abc-123',
            'name' => 'hello.txt',
            'size' => 5,
        ]);
});

it('reads only the part named after its own file key when several ride the same request', function (): void {
    [$component, $event] = uploadToLiveStateHost('schema.upload', 'second', [
        'sylament_upload_first' => fixtureUpload('first.txt', 'one'),
        'sylament_upload_second' => fixtureUpload('second.txt', 'two'),
    ]);

    expect($event['payload']['result'])->toBe(['ok' => true, 'fileKey' => 'second'])
        ->and($component->component()->data['upload']['name'])->toBe('second.txt');
});

it('answers null for a component that does not accept uploads', function (): void {
    [, $event] = uploadOneToLiveStateHost('schema.title', 'abc');

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a file key outside [A-Za-z0-9-]{1,64}', function (string $fileKey): void {
    [, $event] = uploadToLiveStateHost('schema.upload', $fileKey, ['sylament_upload_' . $fileKey => fixtureUpload()]);

    expect($event['payload']['result'])->toBeNull();
})->with([
    'a dot' => 'a.b',
    'a slash' => 'a/b',
    'a traversal' => '../etc',
    'an underscore' => 'a_b',
    'empty' => '',
    'too long' => ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
]);

it('answers null when the request carries no part under the key', function (): void {
    [, $event] = uploadToLiveStateHost('schema.upload', 'abc', []);

    expect($event['payload']['result'])->toBeNull();
});

it('answers null when the part rides under another key', function (): void {
    [, $event] = uploadToLiveStateHost('schema.upload', 'abc', ['sylament_upload_other' => fixtureUpload()]);

    expect($event['payload']['result'])->toBeNull();
});

it('carries the file key in the result payload so concurrent uploads can be told apart', function (): void {
    [, $event] = uploadOneToLiveStateHost('schema.upload', 'abc-123');

    expect($event['payload']['fileKey'])->toBe('abc-123');
});

it('answers null for a hidden component', function (): void {
    [, $event] = uploadOneToLiveStateHost('schema.hiddenUpload', 'abc');

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for a component that refuses outright', function (): void {
    [, $event] = uploadOneToLiveStateHost('schema.disabledUpload', 'abc');

    expect($event['payload']['result'])->toBeNull();
});

it('answers null for an unknown component key, and still re-renders', function (): void {
    [$component, $event] = uploadOneToLiveStateHost('schema.doesNotExist', 'abc');

    expect($event['payload']['result'])->toBeNull()
        ->and((string) $component->render())->toContain('data-model="data.title"');
});

it("leaves the seam's own dispatch with a null file key", function (): void {
    $component = test()->createLiveComponent('LiveStateHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.title',
        'method' => 'echoForJs',
        'arguments' => ['value' => 'x'],
    ]);

    $event = $component->getDispatchedBrowserEvent($component->render(), 'sylament:schema-component-method-result');

    expect($event['payload'])->toBe([
        'componentKey' => 'schema.title',
        'method' => 'echoForJs',
        'fileKey' => null,
        'result' => ['x'],
    ]);
});
