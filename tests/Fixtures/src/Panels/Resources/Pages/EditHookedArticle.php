<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Panels\HookLog;
use Sylament\Tests\Fixtures\Panels\Resources\HookedArticleResource;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Records each save hook in the request's {@see HookLog}, and halts in `beforeSave()`
 * when the stored record's title is {@see self::HALTING_TITLE}.
 */
#[AsLiveComponent]
final class EditHookedArticle extends EditRecord
{
    public const string HALTING_TITLE = 'Halt before saving';

    protected static string $resource = HookedArticleResource::class;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    protected function beforeValidate(): void
    {
        $this->hookLog->record('beforeValidate');
    }

    protected function afterValidate(): void
    {
        $this->hookLog->record('afterValidate');
    }

    protected function beforeSave(): void
    {
        $this->hookLog->record('beforeSave');

        $record = $this->getRecord();

        if ($record instanceof HookedArticle && $record->title === self::HALTING_TITLE) {
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        $this->hookLog->record('afterSave');
    }
}
