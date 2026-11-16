<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use Symfony\Component\Validator\Constraints as Assert;

class VettedAuthor
{
    #[Assert\Length(min: 3, minMessage: 'The author name is too short.')]
    public ?string $name = null;
}
