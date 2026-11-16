<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * The nested object a {@see CatalogueCard} holds, constructible without arguments, with
 * an address of its own that may not be null.
 */
class CardAuthor
{
    public ?string $name = null;

    public ?string $email = null;

    public CardAddress $address;
}
