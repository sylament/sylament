<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Emblem;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Guild;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A relationship select onto {@see Emblem} whose two option actions carry the
 * `mutateDataUsing()` the test names: upper-casing the mapped label, adding a
 * `motto` no component maps, or filling a label only a `Hidden` field maps.
 * Either arm can also take its write over with a `createOptionUsing()` or an
 * `updateOptionUsing()` of its own, which reads the added `motto`.
 */
#[AsLiveComponent(name: 'SelectOptionDataForm', template: '@FormsFixtures/components/SelectOptionDataForm.html.twig')]
class SelectOptionDataForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public const string UPPERCASES = 'uppercases';

    public const string ADDS_UNMAPPED = 'adds-unmapped';

    public const string FILLS_HIDDEN = 'fills-hidden';

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public ?string $mutation = null;

    #[LiveProp]
    public bool $takesCreateOver = false;

    #[LiveProp]
    public bool $takesUpdateOver = false;

    public function mount(): void
    {
        $this->fill();
    }

    public function schema(Schema $schema): Schema
    {
        $select = Select::make('emblem')
            ->relationship(titleAttribute: 'label')
            ->createOptionForm([
                TextInput::make('code'),
                $this->mutation === self::FILLS_HIDDEN ? Hidden::make('label')->required(false) : TextInput::make('label'),
            ])
            ->editOptionForm([TextInput::make('label')])
            ->createOptionAction(fn (Action $action): Action => $this->mutate($action))
            ->editOptionAction(fn (Action $action): Action => $this->mutate($action));

        if ($this->takesCreateOver) {
            $select->createOptionUsing(static function (Select $component, array $data): string {
                $emblem = new Emblem();
                $emblem->setCode((string) $data['code']);
                $emblem->setLabel(sprintf('%s / %s', $data['label'], $data['motto']));

                $reader = $component->getAssociationReader();
                $reader->persist($emblem);
                $reader->flush();

                return $reader->getRecordKey($emblem);
            });
        }

        if ($this->takesUpdateOver) {
            $select->updateOptionUsing(static function (Select $component, array $data): void {
                $emblem = $component->getSelectedRecord();
                assert($emblem instanceof Emblem);

                $emblem->setLabel(sprintf('%s / %s', $data['label'], $data['motto']));

                $component->getAssociationReader()->flush();
            });
        }

        return $schema
            ->statePath('data')
            ->record(Guild::class)
            ->components([$select]);
    }

    private function mutate(Action $action): Action
    {
        return match ($this->mutation) {
            null => $action,
            self::UPPERCASES => $action->mutateDataUsing(static fn (array $data): array => [...$data, 'label' => mb_strtoupper((string) $data['label'])]),
            self::ADDS_UNMAPPED => $action->mutateDataUsing(static fn (array $data): array => [...$data, 'motto' => 'Brave']),
            self::FILLS_HIDDEN => $action->mutateDataUsing(static fn (array $data): array => [...$data, 'label' => 'Stamped']),
        };
    }
}
