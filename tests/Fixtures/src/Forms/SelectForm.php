<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\Select;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One select per way a select reads, casts and guards its state: a static map with a
 * disabled option, a list with and without item bounds, a backed enum single and
 * multiple, `boolean()`, a static map beside a label closure, labels by closure only
 * (single and multiple), none of either (single, and a multiple carrying only the single
 * closure), a required searchable one, a search closure that records what it was handed
 * in the request's {@see HookLog} beside a label closure capitalising the value, a map
 * following a live field, a message for the `Choice`, and an `in()` list. A save validates
 * every field, so a case asserts the errors of the field it fills.
 */
#[AsLiveComponent(name: 'Forms:SelectForm', template: '@Fixtures/forms/SelectForm.html.twig')]
class SelectForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

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
        $plants = ['fern' => 'Fern', 'moss' => 'Moss'];

        return $schema
            ->statePath('data')
            ->components([
                Select::make('single')
                    ->options([1 => 'One', 2 => 'Two', 3 => 'Three'])
                    ->disableOptionWhen(static fn (int|string $value): bool => (string) $value === '3'),
                Select::make('multiple')->multiple()->reorderable()->options(['a' => 'Apple', 'b' => 'Banana', 'c' => 'Cherry']),
                Select::make('bounded')->multiple()->minItems(1)->maxItems(2)->options(['a' => 'Apple', 'b' => 'Banana', 'c' => 'Cherry']),
                Select::make('enum')->enum(StringBackedLetter::class),
                Select::make('letters')->multiple()->enum(StringBackedLetter::class),
                Select::make('boolean')->boolean(),
                Select::make('labelled')
                    ->getOptionLabelUsing(static fn (mixed $value): ?string => is_string($value) ? ($plants[$value] ?? null) : null)
                    ->disableOptionWhen(static fn (mixed $value): bool => $value === 'moss'),
                Select::make('mapped')->options($plants)->getOptionLabelUsing(static fn (): string => 'Any plant'),
                Select::make('labelledMany')
                    ->multiple()
                    ->getOptionLabelsUsing(static fn (array $values): array => array_intersect_key([...$plants, 'bare' => ''], array_flip(array_filter($values, is_string(...))))),
                Select::make('unusable'),
                Select::make('unusableMany')->multiple()->getOptionLabelUsing(static fn (): string => 'Fern'),
                Select::make('chosen')->options($plants)->searchable()->required(),
                Select::make('searchable')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search, string $searchQuery, string $query): array {
                        $this->hookLog->record("search: {$search} {$searchQuery} {$query}");

                        return ['fern' => "Fern ({$search})"];
                    })
                    ->getOptionLabelUsing(static fn (string $value): string => ucfirst($value)),
                Select::make('kind')->options(['tree' => 'Tree', 'herb' => 'Herb'])->live(),
                Select::make('species')->options(static fn (Get $get): array => $get('kind') === 'tree' ? ['oak' => 'Oak'] : ['mint' => 'Mint']),
                Select::make('messaged')->options($plants)->validationMessages([Choice::class => 'Pick a listed plant.']),
                Select::make('restricted')->options($plants)->in(['fern', 'yew']),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
