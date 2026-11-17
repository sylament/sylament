<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\ReplicateAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\AtelierStock;
use Sylament\Tests\Fixtures\Entity\BlankCodedReel;
use Sylament\Tests\Fixtures\Entity\CallbackCodedReel;
use Sylament\Tests\Fixtures\Entity\ConstructorCodedBadge;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\ListenedRegisterLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host with no table, carrying the replicate actions a test mounts by name, each bound
 * to the first seeded record of its class: `Article` variants with and without a modal,
 * `PropagatedCutting` variants named `…Cutting…` (the cutting named Rose, or Graft for the
 * one whose parent is left off), `Tally`, and the key-shaped records. The `OrderLine` and
 * `InvoiceLine` variants are named `replicate<Class><Variant>` and replicate the line
 * numbered 2. `replicateWatched` hands its schema the recording reader, and the hooks and
 * `using()` closures record into the `HookLog`.
 */
#[AsLiveComponent(name: 'Actions:ReplicateHost', template: '@Fixtures/actions/ReplicateHost.html.twig')]
class ReplicateHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $log,
        private readonly RecordingAssociationReader $reader,
    ) {
    }

    public function schemas(): array
    {
        return [];
    }

    public function replicateAction(): ReplicateAction
    {
        return $this->articleAction()->schema([TextInput::make('title')]);
    }

    public function replicateUnboundAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateUnbound');
    }

    public function replicateExcludedAction(): ReplicateAction
    {
        return $this->articleAction('replicateExcluded')
            ->excludeAttributes(['title'])
            ->schema([TextInput::make('title')]);
    }

    public function replicateMutatedAction(): ReplicateAction
    {
        return $this->articleAction('replicateMutated')
            ->mutateRecordDataUsing($this->markCopy(...))
            ->schema([TextInput::make('title')]);
    }

    public function replicateUniqueAction(): ReplicateAction
    {
        return $this->articleAction('replicateUnique')->schema([TextInput::make('title')->unique(ignoreRecord: true)]);
    }

    public function replicateDisabledOnEditAction(): ReplicateAction
    {
        return $this->articleAction('replicateDisabledOnEdit')->schema([TextInput::make('title')->disabledOn('edit')]);
    }

    public function replicateBareAction(): ReplicateAction
    {
        return $this->articleAction('replicateBare');
    }

    public function replicateBareMutatedAction(): ReplicateAction
    {
        return $this->articleAction('replicateBareMutated')->mutateRecordDataUsing($this->markCopy(...));
    }

    public function replicateBareMistypedAction(): ReplicateAction
    {
        return $this->articleAction('replicateBareMistyped')->mutateRecordDataUsing(fn (array $data): array => [...$data, 'id' => 9]);
    }

    public function replicateHookedAction(): ReplicateAction
    {
        return $this->articleAction('replicateHooked')
            ->schema([TextInput::make('title')])
            ->beforeReplicaSaved(function (object $replica): void {
                if ($replica instanceof Article) {
                    $this->log->record(sprintf('beforeReplicaSaved() got %s, %s', $replica->title, isset($replica->id) ? 'saved' : 'unsaved'));
                    $replica->title = 'Hooked';
                }
            });
    }

    public function replicateAfterAction(): ReplicateAction
    {
        return $this->articleAction('replicateAfter')
            ->after(function (?object $replica): void {
                if ($replica instanceof Article) {
                    $this->log->record(sprintf('after() got replica %d', $replica->id));
                }
            });
    }

    public function replicateUsingAction(): ReplicateAction
    {
        return $this->articleAction('replicateUsing')
            ->schema([TextInput::make('title')])
            ->using($this->logUsing(...));
    }

    public function replicateBareUsingAction(): ReplicateAction
    {
        return $this->articleAction('replicateBareUsing')->using($this->logUsing(...));
    }

    public function replicateUnmappedAction(): ReplicateAction
    {
        return $this->articleAction('replicateUnmapped')
            ->schema([TextInput::make('title')])
            ->mutateDataUsing(fn (array $data): array => [...$data, 'unmapped' => 1]);
    }

    public function replicateOverwrittenAction(): ReplicateAction
    {
        return $this->articleAction('replicateOverwritten')
            ->schema([TextInput::make('title')])
            ->mutateDataUsing(fn (array $data): array => [...$data, 'title' => 'By the hook']);
    }

    public function replicateWatchedAction(): ReplicateAction
    {
        return $this->articleAction('replicateWatched')
            ->schema(function (Schema $schema): Schema {
                $schema->setAssociationReader($this->reader);

                return $schema->components([TextInput::make('title')]);
            });
    }

    public function replicateTallyExcludedAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateTallyExcluded')
            ->record(fn (): ?Tally => $this->original(Tally::class))
            ->excludeAttributes(['count']);
    }

    public function replicateCuttingAction(): ReplicateAction
    {
        return $this->cuttingAction('replicateCutting')->schema([TextInput::make('name')]);
    }

    public function replicateCuttingOrphanedAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateCuttingOrphaned')
            ->record(fn (): ?PropagatedCutting => $this->original(PropagatedCutting::class, ['name' => 'Graft']))
            ->excludeAttributes(['parent']);
    }

    public function replicateCuttingOriginExcludedAction(): ReplicateAction
    {
        return $this->cuttingAction('replicateCuttingOriginExcluded')
            ->excludeAttributes(['origin'])
            ->schema([TextInput::make('name'), TextInput::make('origin.nursery')]);
    }

    public function replicateCuttingNurseryExcludedAction(): ReplicateAction
    {
        return $this->cuttingAction('replicateCuttingNurseryExcluded')
            ->excludeAttributes(['origin.nursery'])
            ->schema([TextInput::make('name'), TextInput::make('origin.nursery')]);
    }

    public function replicateOrderLineAction(): ReplicateAction
    {
        return $this->lineAction('replicateOrderLine', OrderLine::class)
            ->schema([TextInput::make('order')->integer(), TextInput::make('line')->integer(), TextInput::make('note')]);
    }

    public function replicateInvoiceLineAction(): ReplicateAction
    {
        return $this->lineAction('replicateInvoiceLine', InvoiceLine::class)
            ->schema([TextInput::make('line')->integer(), TextInput::make('note')]);
    }

    public function replicateOrderLineBareAction(): ReplicateAction
    {
        return $this->lineAction('replicateOrderLineBare', OrderLine::class);
    }

    public function replicateInvoiceLineBareAction(): ReplicateAction
    {
        return $this->lineAction('replicateInvoiceLineBare', InvoiceLine::class);
    }

    public function replicateOrderLineShiftedAction(): ReplicateAction
    {
        return $this->lineAction('replicateOrderLineShifted', OrderLine::class)
            ->beforeReplicaSaved(function (object $replica): void {
                if ($replica instanceof OrderLine) {
                    $replica->order = 7;
                    $replica->line = 3;
                }
            });
    }

    public function replicateInvoiceLineShiftedAction(): ReplicateAction
    {
        return $this->lineAction('replicateInvoiceLineShifted', InvoiceLine::class)->beforeReplicaSaved($this->shiftLine(...));
    }

    public function replicateOrderLineModalShiftedAction(): ReplicateAction
    {
        return $this->replicateOrderLineAction()->name('replicateOrderLineModalShifted')->beforeReplicaSaved($this->shiftLine(...));
    }

    public function replicateInvoiceLineModalShiftedAction(): ReplicateAction
    {
        return $this->replicateInvoiceLineAction()->name('replicateInvoiceLineModalShifted')->beforeReplicaSaved($this->shiftLine(...));
    }

    public function replicateVentBareAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateVentBare')->record(fn (): ?GreenhouseVent => $this->original(GreenhouseVent::class));
    }

    public function replicateBulletinAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateBulletin')
            ->record(fn (): ?StampedBulletin => $this->original(StampedBulletin::class))
            ->schema([TextInput::make('scope'), TextInput::make('label')]);
    }

    public function replicateRegisterLineAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateRegisterLine')
            ->record(fn (): ?ListenedRegisterLine => $this->original(ListenedRegisterLine::class))
            ->schema([TextInput::make('register')]);
    }

    public function replicateStockBareAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateStockBare')->record(fn (): ?AtelierStock => $this->original(AtelierStock::class));
    }

    public function replicateStockTubesAction(): ReplicateAction
    {
        return $this->replicateStockBareAction()
            ->name('replicateStockTubes')
            ->schema([TextInput::make('tubes')->integer()]);
    }

    public function replicateBlankReelBareAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateBlankReelBare')->record(fn (): ?BlankCodedReel => $this->original(BlankCodedReel::class));
    }

    public function replicateCallbackReelBareAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateCallbackReelBare')->record(fn (): ?CallbackCodedReel => $this->original(CallbackCodedReel::class));
    }

    public function replicateBadgeAction(): ReplicateAction
    {
        return ReplicateAction::make('replicateBadge')
            ->record(fn (): ?ConstructorCodedBadge => $this->original(ConstructorCodedBadge::class))
            ->schema([TextInput::make('code'), TextInput::make('label')]);
    }

    private function articleAction(?string $name = null): ReplicateAction
    {
        return ReplicateAction::make($name)
            ->record(fn (): ?Article => $this->original(Article::class))
            ->recordTitleAttribute('title');
    }

    private function cuttingAction(string $name): ReplicateAction
    {
        return ReplicateAction::make($name)->record(fn (): ?PropagatedCutting => $this->original(PropagatedCutting::class, ['name' => 'Rose']));
    }

    /**
     * @param class-string<OrderLine|InvoiceLine> $class
     */
    private function lineAction(string $name, string $class): ReplicateAction
    {
        return ReplicateAction::make($name)->record(fn (): ?object => $this->original($class, ['line' => 2]));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function markCopy(array $data): array
    {
        $title = $data['title'] ?? null;

        return [...$data, 'title' => (is_string($title) ? $title : '') . ' (copy)'];
    }

    private function shiftLine(object $replica): void
    {
        if ($replica instanceof OrderLine || $replica instanceof InvoiceLine) {
            $replica->line = 3;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function logUsing(array $data, object $record, ?Schema $schema): object
    {
        $this->log->record(sprintf(
            'using() got [%s] and a %s, with %s',
            implode(', ', array_keys($data)),
            $record::class,
            $schema instanceof Schema ? 'a schema holding ' . json_encode($schema->getRawState()) : 'no schema',
        ));

        return $record;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     * @param array<string, mixed> $criteria
     *
     * @return T|null
     */
    private function original(string $class, array $criteria = []): ?object
    {
        return $this->entityManager->getRepository($class)->findOneBy($criteria);
    }
}
