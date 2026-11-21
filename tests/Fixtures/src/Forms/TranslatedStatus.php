<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Support\Contracts\HasDescription;
use Sylament\Support\Contracts\HasLabel;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * A status whose cases label themselves with a message from the `fixtures-forms` catalogue; `Draft` describes itself with one too, `Published` answers no description.
 */
enum TranslatedStatus: string implements HasDescription, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';

    public function getLabel(): TranslatableInterface
    {
        return match ($this) {
            self::Draft => new TranslatableMessage('status.draft', domain: 'fixtures-forms'),
            self::Published => new TranslatableMessage('status.published', domain: 'fixtures-forms'),
        };
    }

    public function getDescription(): ?TranslatableInterface
    {
        return match ($this) {
            self::Draft => new TranslatableMessage('status.draft.description', domain: 'fixtures-forms'),
            self::Published => null,
        };
    }
}
