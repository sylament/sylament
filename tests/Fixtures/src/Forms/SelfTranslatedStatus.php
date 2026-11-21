<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A backed status that is a message itself, the way a Symfony application makes an enum translatable, with no `HasLabel`.
 */
enum SelfTranslatedStatus: string implements TranslatableInterface
{
    case Archived = 'archived';
    case Deleted = 'deleted';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Archived => $translator->trans('self_status.archived', domain: 'fixtures-forms', locale: $locale),
            self::Deleted => $translator->trans('self_status.deleted', domain: 'fixtures-forms', locale: $locale),
        };
    }
}
