<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

/**
 * The forms area's render selectors. A side of the input wrapper is named by its
 * `sy-` class, because which side an affix sits on is the stylesheet's contract;
 * what sits there is asserted by its text, its role or its element.
 */
final class Selectors
{
    public const string PREFIX = '.sy-input-wrp-prefix';

    public const string SUFFIX = '.sy-input-wrp-suffix';

    public const string INLINE_PREFIX = '.sy-input-wrp-prefix.sy-inline';

    public const string PREFIX_ICON = '.sy-input-wrp-prefix svg';

    public const string SUFFIX_ICON = '.sy-input-wrp-suffix svg';

    public const string PREFIX_ACTIONS = '.sy-input-wrp-prefix button';

    public const string SUFFIX_ACTIONS = '.sy-input-wrp-suffix button';
}
