<?php

declare(strict_types=1);

namespace Sylament\Support\Twig;

/**
 * Alias names under which apps can override the icons the support components
 * render (via IconManager registration).
 */
class SupportIconAlias
{
    public const string SECTION_COLLAPSE_BUTTON = 'section.collapse-button';

    public const string MODAL_CLOSE_BUTTON = 'modal.close-button';
}
