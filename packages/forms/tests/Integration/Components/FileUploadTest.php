<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use League\Flysystem\FilesystemOperator;
use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\Schema;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;

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

it('carries the crop, resize, exif and transform values, absent when unset and defaulted when true', function (): void {
    $bare = fileUploadRoot(FileUpload::make('cover'));
    $avatar = fileUploadRoot(FileUpload::make('avatar')->avatar()->orientImagesFromExif(false));

    expect($bare->attr('data-sylament--forms--file-upload-image-crop-aspect-ratio-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-image-resize-target-width-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-image-resize-upscale-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-orient-images-from-exif-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-transform-images-value'))->toBe('false')
        ->and($bare->attr('data-sylament--forms--file-upload-image-editor-value'))->toBe('false')
        ->and($bare->attr('data-sylament--forms--file-upload-image-editor-mode-value'))->toBe('1')
        ->and($bare->attr('data-sylament--forms--file-upload-editable-svgs-value'))->toBeNull()
        ->and($avatar->attr('data-sylament--forms--file-upload-image-crop-aspect-ratio-value'))->toBe('1:1')
        ->and($avatar->attr('data-sylament--forms--file-upload-image-resize-mode-value'))->toBe('cover')
        ->and($avatar->attr('data-sylament--forms--file-upload-image-resize-target-width-value'))->toBe('500')
        ->and($avatar->attr('data-sylament--forms--file-upload-image-resize-target-height-value'))->toBe('500')
        ->and($avatar->attr('data-sylament--forms--file-upload-image-resize-upscale-value'))->toBe('false')
        ->and($avatar->attr('data-sylament--forms--file-upload-orient-images-from-exif-value'))->toBe('false')
        ->and($avatar->attr('data-sylament--forms--file-upload-transform-images-value'))->toBe('true')
        ->and($avatar->attr('data-sylament--forms--file-upload-image-editor-viewport-width-value'))->toBe('360');
});

it('carries the editor values, the nullable ones absent and the true-defaulted ones dropped', function (): void {
    $editor = fileUploadRoot(
        FileUpload::make('cover')->imageEditor()->circleCropper()->imageEditorEmptyFillColor('#ffffff')
            ->imageEditorViewportWidth(800)->imageEditorViewportHeight(600)->confirmSvgEditing()->editableSvgs(false),
    );
    $auto = fileUploadRoot(FileUpload::make('cover')->imageAspectRatio('16:9')->automaticallyOpenImageEditorForAspectRatio());
    $bare = fileUploadRoot(FileUpload::make('cover'));

    expect($editor->attr('data-sylament--forms--file-upload-image-editor-value'))->toBe('true')
        ->and($editor->attr('data-sylament--forms--file-upload-image-editor-explicitly-enabled-value'))->toBe('true')
        ->and($editor->attr('data-sylament--forms--file-upload-circle-cropper-value'))->toBe('true')
        ->and($editor->attr('data-sylament--forms--file-upload-image-editor-empty-fill-color-value'))->toBe('#ffffff')
        ->and($editor->attr('data-sylament--forms--file-upload-image-editor-viewport-width-value'))->toBe('800')
        ->and($editor->attr('data-sylament--forms--file-upload-image-editor-viewport-height-value'))->toBe('600')
        ->and($editor->attr('data-sylament--forms--file-upload-confirm-svg-editing-value'))->toBe('true')
        ->and($editor->attr('data-sylament--forms--file-upload-editable-svgs-value'))->toBe('false')
        ->and($editor->attr('data-sylament--forms--file-upload-confirm-svg-editing-message-value'))->toStartWith('Editing SVG files is not recommended')
        ->and($editor->attr('data-sylament--forms--file-upload-disabled-svg-editing-message-value'))->toBe('Editing SVG files is disabled as it can result in quality loss when scaling.')
        ->and($auto->attr('data-sylament--forms--file-upload-image-editor-value'))->toBe('true')
        ->and($auto->attr('data-sylament--forms--file-upload-image-editor-explicitly-enabled-value'))->toBe('false')
        ->and((float) $auto->attr('data-sylament--forms--file-upload-automatically-open-image-editor-for-aspect-ratio-value'))->toEqualWithDelta(16 / 9, 0.0001)
        ->and($bare->attr('data-sylament--forms--file-upload-image-resize-mode-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-image-resize-target-height-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-image-editor-empty-fill-color-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-image-editor-viewport-height-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-automatically-open-image-editor-for-aspect-ratio-value'))->toBeNull()
        ->and($bare->attr('data-sylament--forms--file-upload-circle-cropper-value'))->toBe('false')
        ->and($bare->attr('data-sylament--forms--file-upload-confirm-svg-editing-value'))->toBe('false');
});

it('renders no editor dialog unless the editor is on, and none while disabled', function (): void {
    expect(renderFileUpload(FileUpload::make('cover'))->filter('dialog.sy-fo-file-upload-editor')->count())->toBe(0)
        ->and(renderFileUpload(FileUpload::make('cover')->imageEditor()->disabled())->filter('dialog.sy-fo-file-upload-editor')->count())->toBe(0);
});

it('renders the editor as a live-ignored dialog with its toolbar, inputs and footer', function (): void {
    $crawler = renderFileUpload(
        FileUpload::make('cover')->imageEditor()->circleCropper()->imageEditorAspectRatioOptions([null, '16:9', '4:3']),
    );
    $dialog = $crawler->filter('dialog.sy-fo-file-upload-editor');

    expect($dialog->count())->toBe(1)
        ->and($dialog->attr('data-live-ignore'))->not->toBeNull()
        ->and($dialog->attr('aria-label'))->toBe('Image editor')
        ->and($dialog->attr('class'))->toContain('sy-fo-file-upload-editor-circle-cropper')
        ->and($dialog->attr('class'))->not->toContain('sy-fo-file-upload-editor-crop-only')
        ->and($dialog->attr('data-sylament--forms--file-upload-target'))->toBe('editor')
        ->and($dialog->attr('data-action'))->toBe('cancel->sylament--forms--file-upload#editorCancel')
        ->and($dialog->filter('img.sy-fo-file-upload-editor-image[data-sylament--forms--file-upload-target="editorImage"]')->count())->toBe(1)
        ->and($dialog->filter('[data-sylament--forms--file-upload-target="editorField"]')->count())->toBe(5)
        ->and($dialog->filter('[data-sylament--forms--file-upload-target="editorField"]')->first()->attr('data-sylament--forms--file-upload-field-param'))->toBe('x')
        ->and($dialog->filter('[data-sylament--forms--file-upload-target="editorField"]')->first()->attr('data-action'))->toBe('keydown->sylament--forms--file-upload#editorApplyField blur->sylament--forms--file-upload#editorApplyField')
        ->and($dialog->filter('.sy-fo-file-upload-editor-control-panel-main .sy-btn-group')->count())->toBe(4)
        ->and($dialog->filter('button[data-action="click->sylament--forms--file-upload#editorZoom"]')->count())->toBe(2)
        ->and($dialog->filter('button[data-action="click->sylament--forms--file-upload#editorMove"][data-sylament--forms--file-upload-x-param="-10"]')->count())->toBe(1)
        ->and($dialog->filter('button[data-action="click->sylament--forms--file-upload#editorSetDragMode"]')->first()->attr('aria-label'))->toBe('Drag mode "move"')
        ->and($dialog->filter('[data-sylament--forms--file-upload-target="editorRatio"]')->count())->toBe(3)
        ->and($dialog->filter('[data-sylament--forms--file-upload-target="editorRatio"]')->first()->attr('data-sylament--forms--file-upload-ratio-param'))->toBe('NaN')
        ->and($dialog->filter('[data-sylament--forms--file-upload-target="editorRatio"]')->first()->text())->toBe('Free')
        ->and($dialog->filter('.sy-fo-file-upload-editor-control-panel-footer button')->count())->toBe(3)
        ->and($dialog->filter('.sy-fo-file-upload-editor-control-panel-footer button')->last()->attr('data-action'))->toBe('click->sylament--forms--file-upload#editorSave');
});

it('renders the crop-only editor under the auto-open flag, with neither inputs nor toolbar', function (): void {
    $dialog = renderFileUpload(FileUpload::make('cover')->imageAspectRatio('16:9')->automaticallyOpenImageEditorForAspectRatio())
        ->filter('dialog.sy-fo-file-upload-editor');

    expect($dialog->count())->toBe(1)
        ->and($dialog->attr('class'))->toContain('sy-fo-file-upload-editor-crop-only')
        ->and($dialog->filter('.sy-fo-file-upload-editor-control-panel-main')->count())->toBe(0)
        ->and($dialog->filter('.sy-fo-file-upload-editor-control-panel-footer button')->count())->toBe(2)
        ->and($dialog->filter('.sy-fo-file-upload-editor-control-panel-footer button')->first()->attr('data-action'))->toBe('click->sylament--forms--file-upload#editorSave');
});

it('renders the aspect-ratio group only from two options up, and all thirteen toolbar buttons', function (): void {
    expect(renderFileUpload(FileUpload::make('cover')->imageEditor())->filter('[data-sylament--forms--file-upload-target="editorRatio"]')->count())->toBe(0)
        ->and(renderFileUpload(FileUpload::make('cover')->imageEditor()->imageEditorAspectRatioOptions(['1:1']))->filter('[data-sylament--forms--file-upload-target="editorRatio"]')->count())->toBe(0)
        ->and(renderFileUpload(FileUpload::make('cover')->imageEditor()->imageEditorAspectRatioOptions([null, '1:1']))->filter('[data-sylament--forms--file-upload-target="editorRatio"]')->count())->toBe(2)
        ->and(renderFileUpload(FileUpload::make('cover')->imageEditor())->filter('.sy-fo-file-upload-editor-control-panel-main .sy-btn-group button')->count())->toBe(13);
});

it('lets a registered alias replace an editor toolbar icon at render time', function (): void {
    // The heroicon the override names is the 24-box outline star, where the default is a 20-box solid icon.
    $this::getContainer()->get(IconManager::class)->register([
        FormsIconAlias::COMPONENTS_FILE_UPLOAD_EDITOR_ACTIONS_ZOOM_IN => 'heroicons:star',
    ]);

    $button = renderFileUpload(FileUpload::make('cover')->imageEditor())
        ->filter('button[data-action="click->sylament--forms--file-upload#editorZoom"]')->first();

    expect($button->filter('svg')->attr('viewBox'))->toBe('0 0 24 24');
});

it('carries a nested constraint on the collection set, beside the File split', function (): void {
    $upload = FileUpload::make('cover')->nestedRecursiveConstraint(new Callback(static fn (): null => null));

    $host = new FieldRenderHost();
    $host->data = ['cover' => ['a.png' => 'a.png']];

    Schema::make($host)->statePath('data')->components([$upload])->getComponents();

    $all = array_values(array_filter($upload->getValidationConstraints(), static fn (object $c): bool => $c instanceof All));

    expect($all)->toHaveCount(1)
        ->and($all[0]->constraints[0])->toBeInstanceOf(Callback::class);
});
