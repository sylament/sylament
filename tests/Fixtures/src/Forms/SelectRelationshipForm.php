<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A new {@see Seedling}'s form whose selects all pick its {@see Garden}, one per way a
 * relationship select lists, searches, labels and creates its options: the full map with
 * both option forms and their modal headings, an edit modal filled from an array, a
 * searchable map with and without `preload()` and a limit, a search forced
 * case-insensitive and one forced not to be, a scope leaving `Moss` out, a label closure
 * declared before and after `relationship()`, a create closure declared before and after
 * it (the later one recording in the request's {@see HookLog}), and option actions whose
 * `mutateDataUsing()` adds an unmapped path, sets a `Hidden` one, or overwrites the
 * mapped name.
 */
#[AsLiveComponent(name: 'Forms:SelectRelationshipForm', template: '@Fixtures/forms/SelectRelationshipForm.html.twig')]
class SelectRelationshipForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public const string CUSTOM_LABEL = 'Labelled by hand';

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        $nameForm = [TextInput::make('name')->required()];

        return $schema
            ->statePath('data')
            ->record(Seedling::class)
            ->components([
                Select::make('garden')
                    ->relationship('garden', 'name')
                    ->manageOptionForm($nameForm)
                    ->createOptionModalHeading('New garden')
                    ->editOptionModalHeading('Rename garden'),
                Select::make('filledByHand')
                    ->relationship('garden', 'name')
                    ->editOptionForm($nameForm)
                    ->fillEditOptionActionFormUsing(static fn (): array => ['name' => self::CUSTOM_LABEL]),
                Select::make('searched')->relationship('garden', 'name')->searchable(),
                Select::make('preloaded')->relationship('garden', 'name')->searchable()->preload()->optionsLimit(2),
                Select::make('caseless')->relationship('garden', 'name')->searchable()->forceSearchCaseInsensitive(),
                Select::make('caseful')->relationship('garden', 'name')->searchable()->forceSearchCaseInsensitive(false),
                Select::make('scoped')
                    ->relationship('garden', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name <> 'Moss'"))
                    ->searchable(),
                Select::make('labelledBefore')
                    ->getOptionLabelUsing(static fn (): string => self::CUSTOM_LABEL)
                    ->relationship('garden', 'name')
                    ->searchable(),
                Select::make('labelledAfter')
                    ->relationship('garden', 'name')
                    ->searchable()
                    ->getOptionLabelUsing(static fn (): string => self::CUSTOM_LABEL),
                Select::make('createdBefore')
                    ->createOptionUsing(function (): string {
                        $this->hookLog->record('created by hand');

                        return 'by-hand';
                    })
                    ->relationship('garden', 'name')
                    ->createOptionForm($nameForm),
                Select::make('createdAfter')
                    ->relationship('garden', 'name')
                    ->createOptionForm($nameForm)
                    ->createOptionUsing(function (Action $action, array $data): string {
                        $this->hookLog->record(sprintf('%s: %s', $action->getName(), is_string($data['name'] ?? null) ? $data['name'] : ''));

                        return 'by-hand';
                    }),
                Select::make('unmapped')
                    ->relationship('garden', 'name')
                    ->createOptionForm($nameForm)
                    ->createOptionAction(static fn (Action $action): Action => $action->mutateDataUsing(static fn (array $data): array => [...$data, 'nickname' => 'Ferny'])),
                Select::make('hiddenMapped')
                    ->relationship('garden', 'name')
                    ->createOptionForm([Hidden::make('name')->required(false)])
                    ->createOptionAction(static fn (Action $action): Action => $action->mutateDataUsing(static fn (array $data): array => [...$data, 'name' => 'Hidden fern'])),
                Select::make('overwritten')
                    ->relationship('garden', 'name')
                    ->createOptionForm($nameForm)
                    ->createOptionAction(static fn (Action $action): Action => $action->mutateDataUsing(static fn (array $data): array => [...$data, 'name' => mb_strtoupper(is_string($data['name'] ?? null) ? $data['name'] : '')])),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
