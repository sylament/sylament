<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

/**
 * The forms area's render selectors. A side of the input wrapper is named by its
 * `sy-` class, because which side an affix sits on is the stylesheet's contract.
 */
final class Selectors
{
    public const string PREFIX_LABEL = '.sy-input-wrp-prefix .sy-input-wrp-label';

    public const string SUFFIX_LABEL = '.sy-input-wrp-suffix .sy-input-wrp-label';

    public const string INLINE_PREFIX = '.sy-input-wrp-prefix.sy-inline';

    public const string PREFIX_ICON = '.sy-input-wrp-prefix .sy-icon';

    public const string SUFFIX_ICON = '.sy-input-wrp-suffix .sy-icon';

    public const string PREFIX_ACTIONS = '.sy-input-wrp-prefix button';

    public const string SUFFIX_ACTIONS = '.sy-input-wrp-suffix button';

    /**
     * Gray paints an element carrying no colour class, so a gray icon is an uncoloured one.
     */
    public const string GRAY = ':not(.sy-color)';

    public const string SMALL_ICON_BUTTON = '.sy-icon-btn.sy-size-sm';

    public const string LINK = '.sy-link';

    public static function color(string $name): string
    {
        return ".sy-color-{$name}";
    }
}
