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
 * container FilePond builds inside, and the image editor's dialog; a toggle's control root and track, and a
 * toggle-buttons option's button label, which must follow its input, named by the `sy-fo-`
 * class the stylesheet lays out or paints each by.
 *
 * A repeater's item in its default and simple views, one by its key, and its add button.
 * A simple view's item, a table view's row (one by its key) and header cell, and the add row.
 *
 * A select's `<select>` by the `sy-fo-` class that marks it, the same element carrying the
 * JS control's Stimulus controller, the one with no controller, and the inline-prefix class
 * that strips its padding, the class being the behaviour; a checkbox list's options grid, and
 * the label and the description an option shows, by the `sy-fo-` class each carries.
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

    public const string TOGGLE = '.sy-fo-toggle';

    public const string TOGGLE_TRACK = '.sy-fo-toggle-track';

    /**
     * The adjacency the selected-state stylesheet reads, `input:checked + label.sy-btn`.
     */
    public const string TOGGLE_BUTTONS_OPTION = 'input.sy-fo-toggle-buttons-input + label.sy-btn';

    public const string REPEATER_ITEM = 'li[data-item-key]';

    public const string REPEATER_ADD_BUTTON = '.sy-fo-repeater-add button';

    public const string SIMPLE_REPEATER_ITEM = 'li.sy-fo-simple-repeater-item';

    public const string TABLE_REPEATER_ROW = 'tr[data-item-key]';

    public const string TABLE_REPEATER_HEADER_CELL = '.sy-fo-table-repeater thead th';

    public const string REPEATER_ADD = '.sy-fo-repeater-add';

    public const string SELECT = 'select.sy-fo-select';

    public const string JS_CONTROL = 'select.sy-fo-select[data-controller~="sylament--forms--select"]';

    public const string NATIVE_SELECT = 'select.sy-fo-select:not([data-controller])';

    public const string INLINE_PREFIX_SELECT = 'select.sy-fo-select.sy-fo-select-has-inline-prefix';

    public const string CHECKBOX_LIST_OPTIONS = '.sy-fo-checkbox-list-options';

    public const string CHECKBOX_LIST_OPTION_LABEL = '.sy-fo-checkbox-list-option-label';

    public const string CHECKBOX_LIST_OPTION_DESCRIPTION = '.sy-fo-checkbox-list-option-description';

    public static function textInputAction(string $method): string
    {
        return "[data-action=\"sylament--forms--text-input#{$method}\"]:not([data-live-action-param])";
    }

    public static function fileUpload(string $statePath): string
    {
        return self::FILE_UPLOAD . '[data-sylament--forms--file-upload-state-path-value="' . $statePath . '"]';
    }

    public static function repeaterItem(string $key): string
    {
        return "li[data-item-key=\"{$key}\"]";
    }

    public static function tableRepeaterRow(string $key): string
    {
        return "tr[data-item-key=\"{$key}\"]";
    }
}
