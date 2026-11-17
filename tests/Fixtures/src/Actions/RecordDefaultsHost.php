<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host answering the record hooks per action, its record class {@see Product} for
 * every one: `titled` gets a title, `labelled` a label and no title, `unlabelled`
 * neither, `untitled` an empty title, and `defaulted` a default record, `Lamp` with
 * id 9, which `getDefaultActionRecord()` answers for it alone.
 */
#[AsLiveComponent(name: 'Actions:RecordDefaultsHost', template: '@Fixtures/actions/RecordDefaultsHost.html.twig')]
class RecordDefaultsHost implements InteractsWithActions
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

    public function unlabelledAction(): DeleteAction
    {
        return DeleteAction::make('unlabelled');
    }

    public function untitledAction(): DeleteAction
    {
        return DeleteAction::make('untitled');
    }

    public function defaultedAction(): Action
    {
        return Action::make('defaulted');
    }

    public function getDefaultActionRecord(Action $action): ?object
    {
        if ($action->getName() !== 'defaulted') {
            return null;
        }

        $lamp = new Product('Lamp');
        $lamp->id = 9;

        return $lamp;
    }

    public function getDefaultActionRecordClass(Action $action): string
    {
        return Product::class;
    }

    public function getDefaultActionRecordLabel(Action $action): ?string
    {
        return $action->getName() === 'labelled' ? 'stock item' : null;
    }

    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        return match ($action->getName()) {
            'titled' => 'The lamp',
            'untitled' => '',
            default => null,
        };
    }
}
