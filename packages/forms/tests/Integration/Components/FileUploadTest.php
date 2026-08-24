<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use League\Flysystem\FilesystemOperator;
use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * The DOM contract the `file-upload` Stimulus controller is written against: the controller
 * element carries every value and morphs, while the pond subtree it builds into is
 * `data-live-ignore` and never does.
 */

function fixtureStorage(string $name = 'test.storage'): FilesystemOperator
{
    $storage = test()->getContainer()->get($name);

    expect($storage)->toBeInstanceOf(FilesystemOperator::class);

    return $storage;
}

/**
 * @param array<string, mixed> $data
 */
function renderFileUpload(FileUpload $field, array $data = []): Crawler
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return new Crawler(renderToHtml(Schema::make($host)->statePath('data')->components([$field])));
}

/**
 * @param array<string, mixed> $data
 */
function fileUploadRoot(FileUpload $field, array $data = []): Crawler
{
    return renderFileUpload($field, $data)->filter('div.sy-fo-file-upload');
}

it('renders the controller element with its group role and its own label', function (): void {
    $crawler = renderFileUpload(FileUpload::make('cover'));
    $root = $crawler->filter('div.sy-fo-file-upload');

    expect($root->count())->toBe(1)
        ->and($root->attr('class'))->toContain('sy ')
        ->and($root->attr('role'))->toBe('group')
        ->and($root->attr('data-controller'))->toBe('sylament--forms--file-upload')
        ->and($root->attr('aria-labelledby'))->toBe($root->attr('id') . '-label')
        ->and($crawler->filter('div#' . $root->attr('id') . '-label')->text())->toBe('Cover')
        // A `<label for>` would point at an input FilePond takes over, so the wrapper renders the
        // label as a div and the control is reached through `aria-labelledby` instead.
        ->and($crawler->filter('label')->count())->toBe(0);
});

it('carries the whole value map, with both maps serialised as objects when empty', function (): void {
    $root = fileUploadRoot(FileUpload::make('cover')->multiple()->acceptedFileTypes(['image/*']));

    expect($root->attr('data-sylament--forms--file-upload-state-path-value'))->toBe('data.cover')
        ->and($root->attr('data-sylament--forms--file-upload-multiple-value'))->toBe('true')
        ->and($root->attr('data-sylament--forms--file-upload-accepted-file-types-value'))->toBe('["image\/*"]')
        ->and($root->attr('data-sylament--forms--file-upload-max-file-size-value'))->toMatch('/^[0-9]+$/')
        // `[]` and `{}` are the same PHP array; the `(object)` casts are what keep the Stimulus
        // `Object` values one shape whether or not the field holds a file.
        ->and($root->attr('data-sylament--forms--file-upload-uploaded-files-value'))->toBe('{}')
        ->and($root->attr('data-sylament--forms--file-upload-state-value'))->toBe('{}')
        ->and($root->attr('data-sylament--forms--file-upload-mime-type-map-value'))->toBe('{}')
        ->and($root->attr('data-sylament--forms--file-upload-download-action-label-value'))->toBe('Download')
        ->and($root->attr('data-sylament--forms--file-upload-open-action-label-value'))->toBe('Open in new tab')
        ->and($root->attr('data-sylament--forms--file-upload-panel-layout-value'))->toBe('compact');
});

it('omits a null value rather than rendering it', function (): void {
    $root = fileUploadRoot(FileUpload::make('cover'));

    expect($root->attr('data-sylament--forms--file-upload-min-size-value'))->toBeNull()
        ->and($root->attr('data-sylament--forms--file-upload-max-files-value'))->toBeNull()
        ->and($root->attr('data-sylament--forms--file-upload-max-files-message-value'))->toBeNull()
        ->and($root->attr('data-sylament--forms--file-upload-placeholder-value'))->toBeNull();
});

it('spells the sizes FilePond expects and names the max-files message after the label', function (): void {
    $root = fileUploadRoot(FileUpload::make('cover')->minSize(16)->maxSize(2)->maxFiles(3));

    expect($root->attr('data-sylament--forms--file-upload-min-size-value'))->toBe('16KB')
        ->and($root->attr('data-sylament--forms--file-upload-max-files-value'))->toBe('3')
        ->and($root->attr('data-sylament--forms--file-upload-max-files-message-value'))
        ->toBe('The Cover field must not have more than 3 items.');
});

it("caps the client-side size at PHP's own ceiling", function (): void {
    // `maxSize()` is in kibibytes; the value is bytes, and never above what PHP would accept.
    $root = fileUploadRoot(FileUpload::make('cover')->maxSize(1));

    expect((int) $root->attr('data-sylament--forms--file-upload-max-file-size-value'))->toBe(1024);
});

it('renders the extra attributes once, on the field wrapper, leaving the controller its own id', function (): void {
    // `FieldWrapper` already spreads `getExtraAttributes`; a second spread on the controller root
    // would render them twice and — later sources winning — overwrite the id `aria-labelledby`
    // points at.
    $crawler = renderFileUpload(FileUpload::make('cover')->extraAttributes(['data-x' => '1']));

    expect($crawler->filter('[data-x="1"]')->count())->toBe(1)
        ->and($crawler->filter('[data-x="1"]')->attr('class'))->toContain('sy-fo-field')
        ->and($crawler->filter('div.sy-fo-file-upload')->attr('id'))->not->toBeNull();
});

it('hides the pond subtree from the morph and mirrors the input state into it', function (): void {
    $crawler = renderFileUpload(FileUpload::make('cover')->multiple()->disabled());
    $pond = $crawler->filter('[data-sylament--forms--file-upload-target="pond"]');
    $input = $pond->filter('input[type="file"]');

    expect($pond->count())->toBe(1)
        ->and($pond->attr('data-live-ignore'))->not->toBeNull()
        ->and($pond->attr('class'))->toContain('sy-fo-file-upload-input-ctn')
        ->and($input->count())->toBe(1)
        ->and($input->attr('multiple'))->not->toBeNull()
        ->and($input->attr('disabled'))->not->toBeNull()
        ->and($input->attr('name'))->toBeNull()
        ->and($input->attr('required'))->toBeNull()
        ->and($input->attr('data-sylament--forms--file-upload-target'))->toBe('input')
        ->and($input->attr('aria-labelledby'))->toBe($crawler->filter('div.sy-fo-file-upload')->attr('id') . '-label');
});

it('carries the extra input attributes onto the file input', function (): void {
    $input = renderFileUpload(FileUpload::make('cover')->extraInputAttributes(['data-y' => '2']))
        ->filter('input[type="file"]');

    expect($input->attr('data-y'))->toBe('2');
});

it('renders the error target hidden, for the controller to fill', function (): void {
    $error = renderFileUpload(FileUpload::make('cover'))
        ->filter('[data-sylament--forms--file-upload-target="error"]');

    expect($error->count())->toBe(1)
        ->and($error->attr('role'))->toBe('alert')
        ->and($error->attr('hidden'))->not->toBeNull()
        ->and($error->attr('class'))->toBe('sy-fo-file-upload-error-message');
});

it('marks the avatar variant on the root', function (): void {
    expect(fileUploadRoot(FileUpload::make('avatar')->avatar())->attr('class'))
        ->toContain('sy-fo-file-upload-avatar');

    expect(fileUploadRoot(FileUpload::make('cover'))->attr('class'))
        ->not->toContain('sy-fo-file-upload-avatar');
});

it('carries the alignment class the support concern names', function (): void {
    expect(fileUploadRoot(FileUpload::make('cover')->alignCenter())->attr('class'))->toContain('sy-align-center');
});

it('renders a stored file with its metadata and a public url', function (): void {
    fixtureStorage()->write('covers/a.png', 'the bytes');

    $root = fileUploadRoot(
        FileUpload::make('cover')->disk('test.storage')->multiple(),
        ['cover' => ['abc' => 'covers/a.png']],
    );

    $uploadedFiles = json_decode((string) $root->attr('data-sylament--forms--file-upload-uploaded-files-value'), true);

    expect($uploadedFiles)->toHaveKey('abc')
        ->and($uploadedFiles['abc']['name'])->toBe('a.png')
        ->and($uploadedFiles['abc']['size'])->toBe(9)
        ->and($uploadedFiles['abc']['type'])->toBe('image/png')
        ->and($uploadedFiles['abc']['url'])->toBe('/files/covers/a.png');
});

it('hands the browser no url for a private file the storage cannot sign', function (): void {
    // `other.storage` is configured with no `public_url` and no temporary-url generator, and the
    // private arm never falls through to `publicUrl()` — a file marked private gets no link at all
    // rather than a public one.
    fixtureStorage('other.storage')->write('covers/b.png', 'the bytes');

    $root = fileUploadRoot(
        FileUpload::make('cover')->disk('other.storage')->visibility('private')->multiple(),
        ['cover' => ['abc' => 'covers/b.png']],
    );

    $uploadedFiles = json_decode((string) $root->attr('data-sylament--forms--file-upload-uploaded-files-value'), true);

    expect($uploadedFiles['abc']['url'])->toBeNull()
        ->and($uploadedFiles['abc']['name'])->toBe('b.png');
});
