<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

/**
 * A pure enum, with no backing value for a form's state to spell a case by.
 */
enum PlainSuit
{
    case Hearts;
    case Spades;
}
