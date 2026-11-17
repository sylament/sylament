<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

dataset('trigger looks', [
    'a badge' => [static fn (Action $action): Action => $action->badge(), SupportSelectors::BADGE],
    'a button' => [static fn (Action $action): Action => $action, SupportSelectors::BUTTON],
    'a dropdown item' => [static fn (Action $action): Action => $action->grouped(), SupportSelectors::DROPDOWN_ITEM],
    'an icon button' => [static fn (Action $action): Action => $action->iconButton(), SupportSelectors::ICON_BUTTON],
    'a link' => [static fn (Action $action): Action => $action->link(), SupportSelectors::LINK],
]);
