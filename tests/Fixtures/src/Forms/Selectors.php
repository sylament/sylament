<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

/**
 * The markup forms' templates give a test to find: the field wrapper's root by the `data-` hook
 * a validation error scrolls to, its label and content columns, label row, hidden label, the
 * label it renders as a heading for a control that is no labelable element, required mark and
 * error messages; a text field's control and the classes its inline prefix and suffix give it,
 * a text input's controller target and its affix buttons' actions, and the password reveal
 * button by its accessible name; a fused group's root; a toggle's control root, track and on
 * and off icons; a toggle-buttons option's button label, which must follow its input; a
 * repeater's item in its default and simple views, a table view's row and header cell, and the
 * add row and its button; a key-value field's key and value inputs by their accessible names; a
 * select's `<select>`, the same element with and without the JS control's Stimulus controller,
 * the inline-prefix class that strips its padding, and its options past the empty placeholder;
 * a checkbox list's options grid and an option's label and description; a radio's group by its
 * role, its option inputs by their type, and an option's label and description; and a file
 * field's controller element by the state path its value map names, the pond container FilePond
 * builds inside and the image editor's dialog. Each is named by the `sy-fo-` class the
 * stylesheet lays out or paints it by, the class being the behaviour, unless a role, a type, an
 * accessible name or a `data-` hook names it.
 */
final class Selectors
{
    public const string FIELD_WRAPPER = '[data-field-wrapper]';

    public const string LABEL_COLUMN = '.sy-fo-field-label-col';

    public const string LABEL_ROW = '.sy-fo-field-label-ctn';

    public const string LABEL_HEADING = 'div.sy-fo-field-label';

    public const string CONTENT_COLUMN = '.sy-fo-field-content-col';

    public const string REQUIRED_MARK = '.sy-fo-field-label-required-mark';

    public const string HIDDEN_LABEL = 'label' . SupportSelectors::SCREEN_READER_ONLY;

    public const string ERROR_MESSAGE = '[data-validation-error] .sy-fo-field-error-message';

    public const string INPUT = '.sy-fo-input';

    public const string INPUT_HAS_INLINE_PREFIX = '.sy-fo-input-has-inline-prefix';

    public const string INPUT_HAS_INLINE_SUFFIX = '.sy-fo-input-has-inline-suffix';

    public const string SHOW_PASSWORD_BUTTON = 'button[aria-label="Show password"]';

    public const string FUSED_GROUP = '.sy-fo-fused-group';

    public const string TEXT_INPUT_TARGET = '[data-sylament--forms--text-input-target="input"]';

    public const string FILE_UPLOAD = '[data-controller="sylament--forms--file-upload"]';

    public const string FILE_UPLOAD_POND = '[data-sylament--forms--file-upload-target="pond"]';

    public const string FILE_UPLOAD_EDITOR = 'dialog[aria-label="Image editor"]';

    public const string TOGGLE = '.sy-fo-toggle';

    public const string TOGGLE_TRACK = '.sy-fo-toggle-track';

    public const string TOGGLE_ICON_OFF = '.sy-fo-toggle-icon-off';

    public const string TOGGLE_ICON_ON = '.sy-fo-toggle-icon-on';

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

    public const string KEY_VALUE_KEY = 'input[aria-label="Key"]';

    public const string KEY_VALUE_VALUE = 'input[aria-label="Value"]';

    public const string SELECT = 'select.sy-fo-select';

    public const string JS_CONTROL = 'select.sy-fo-select[data-controller~="sylament--forms--select"]';

    public const string NATIVE_SELECT = 'select.sy-fo-select:not([data-controller])';

    public const string OPTION = 'option:not([value=""])';

    public const string INLINE_PREFIX_SELECT = 'select.sy-fo-select.sy-fo-select-has-inline-prefix';

    public const string CHECKBOX_LIST_OPTIONS = '.sy-fo-checkbox-list-options';

    public const string CHECKBOX_LIST_OPTION_LABEL = '.sy-fo-checkbox-list-option-label';

    public const string CHECKBOX_LIST_OPTION_DESCRIPTION = '.sy-fo-checkbox-list-option-description';

    public const string RADIO_GROUP = '[role="radiogroup"]';

    public const string RADIO = 'input[type="radio"]';

    public const string RADIO_OPTION_LABEL = '.sy-fo-radio-label-text p:not(.sy-fo-radio-label-description)';

    public const string RADIO_OPTION_DESCRIPTION = '.sy-fo-radio-label-description';

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
