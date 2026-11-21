<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host answering its record hooks with catalogue messages, its record class {@see Product}
 * for every action: `titled` gets a title, `labelled` a label and no title.
 */
#[AsLiveComponent(name: 'Actions:TranslatedRecordHost', template: '@Fixtures/actions/RecordDefaultsHost.html.twig')]
class TranslatedRecordHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function titledAction(): DeleteAction
    {
        return DeleteAction::make('titled');
    }

    public function labelledAction(): DeleteAction
    {
        return DeleteAction::make('labelled');
    }

    public function getDefaultActionRecordClass(Action $action): string
    {
        return Product::class;
    }

    public function getDefaultActionRecordLabel(Action $action): TranslatableInterface|null
    {
        return $action->getName() === 'labelled' ? new TranslatableMessage('record.label', [], 'fixtures-actions') : null;
    }

    public function getDefaultActionRecordTitle(Action $action): TranslatableInterface|null
    {
        return $action->getName() === 'titled' ? new TranslatableMessage('record.title', [], 'fixtures-actions') : null;
    }
}
