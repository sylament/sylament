<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Answers all four record hooks — and the schema resolver — for every action it
 * hosts, so the fall-through end of the record chain and the resolver arm can be
 * asserted without a panel page. `$recordLabel`
 * is public and nullable so a case can build the one host shape the label chain
 * treats specially: one that owns the class but answers no label.
 */
#[AsLiveComponent(name: 'DefaultRecordHost', template: '@ActionsFixtures/components/DefaultRecordHost.html.twig')]
class DefaultRecordHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public object $record;

    public ?string $recordLabel = 'thing';

    public function __construct()
    {
        $this->record = new class {
            public int $id = 1;

            public string $name = 'The thing';
        };
    }

    public function pingAction(): Action
    {
        return Action::make('ping')->action(fn (): null => null);
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return fn (Schema $schema): Schema => $schema->components([Text::make('resolved')]);
    }

    public function getDefaultActionRecord(Action $action): object|array|null
    {
        return $this->record;
    }

    /**
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return $this->record::class;
    }

    public function getDefaultActionRecordLabel(Action $action): ?string
    {
        return $this->recordLabel;
    }

    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        return 'The thing';
    }
}
