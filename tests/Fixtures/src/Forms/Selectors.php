<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

/**
 * The field wrapper's root, by the `data-` hook a validation error scrolls to, its label
 * and content columns, its label row, the label it renders as a heading for a control
 * that is no labelable element, its required mark and its error messages, and a fused
 * group's root, named by the `sy-fo-` class the stylesheet lays each out by; a text
 * input's control and an affix button its Stimulus controller answers in the browser,
 * with no live click, by the controller's target and action;
 * a file field's controller element, by the state path its value map names, the pond
 * container FilePond builds inside, and the image editor's dialog.
 */
final class Selectors
{
    public const string FIELD_WRAPPER = '[data-field-wrapper]';

    public const string LABEL_COLUMN = '.sy-fo-field-label-col';

    public const string LABEL_ROW = '.sy-fo-field-label-ctn';

    public const string LABEL_HEADING = 'div.sy-fo-field-label';

    public const string CONTENT_COLUMN = '.sy-fo-field-content-col';

    public const string REQUIRED_MARK = '.sy-fo-field-label-required-mark';

    public const string ERROR_MESSAGE = '[data-validation-error] .sy-fo-field-error-message';

    public const string FUSED_GROUP = '.sy-fo-fused-group';

    public const string TEXT_INPUT_TARGET = '[data-sylament--forms--text-input-target="input"]';

    public const string FILE_UPLOAD = '[data-controller="sylament--forms--file-upload"]';

    public const string FILE_UPLOAD_POND = '[data-sylament--forms--file-upload-target="pond"]';

    public const string FILE_UPLOAD_EDITOR = 'dialog[aria-label="Image editor"]';

    public static function textInputAction(string $method): string
    {
        return "[data-action=\"sylament--forms--text-input#{$method}\"]:not([data-live-action-param])";
    }

    public static function fileUpload(string $statePath): string
    {
        return self::FILE_UPLOAD . '[data-sylament--forms--file-upload-state-path-value="' . $statePath . '"]';
    }
}
