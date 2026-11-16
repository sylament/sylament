<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * A plain record declaring its own constraints, on a scalar and, through `Valid`, on the
 * nested author, so the record-validation tail has something to answer.
 */
class VettedCard
{
    public const string WITHDRAWN = 'Withdrawn';

    #[Assert\NotEqualTo(self::WITHDRAWN, message: 'That title is withdrawn.')]
    public ?string $title = null;

    #[Assert\Valid]
    public VettedAuthor $author;

    public function __construct()
    {
        $this->author = new VettedAuthor();
    }
}
