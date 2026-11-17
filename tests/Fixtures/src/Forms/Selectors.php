<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

/**
 * The field wrapper's root, by the `data-` hook a validation error scrolls to, its label
 * row, its required mark and its error messages, named by the `sy-fo-` class the
 * stylesheet lays each out by; a text input's control and an affix button its Stimulus
 * controller answers in the browser, with no live click, by the controller's target and
 * action.
 */
final class Selectors
{
    public const string FIELD_WRAPPER = '[data-field-wrapper]';

    public const string LABEL_ROW = '.sy-fo-field-label-ctn';

    public const string REQUIRED_MARK = '.sy-fo-field-label-required-mark';

    public const string ERROR_MESSAGE = '[data-validation-error] .sy-fo-field-error-message';

    public const string TEXT_INPUT_TARGET = '[data-sylament--forms--text-input-target="input"]';

    public static function textInputAction(string $method): string
    {
        return "[data-action=\"sylament--forms--text-input#{$method}\"]:not([data-live-action-param])";
    }
}
