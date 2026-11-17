<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Support\Contracts\HasDescription;
use Sylament\Support\Contracts\HasLabel;
use Twig\Markup;

/**
 * A delivery mode whose cases label and describe their option: `Express` labels itself
 * with markup, `Pickup` answers no label, so its option is named by the case, `Courier`
 * answers no description and `Pickup` an empty one.
 */
enum DeliveryMode: string implements HasDescription, HasLabel
{
    case Standard = 'standard';
    case Express = 'express';
    case Courier = 'courier';
    case Pickup = 'pickup';

    public function getLabel(): string|Markup|null
    {
        return match ($this) {
            self::Standard => 'Standard delivery',
            self::Express => new Markup('<b>Express</b> delivery', 'UTF-8'),
            self::Courier => 'By courier',
            self::Pickup => null,
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Standard => 'Three to five days',
            self::Express => 'Next day',
            self::Courier => null,
            self::Pickup => '',
        };
    }
}
