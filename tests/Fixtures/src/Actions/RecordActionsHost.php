<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\CharmSatchel;
use Sylament\Tests\Fixtures\Entity\ClaspedLocket;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderCrock;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\QuayMooring;
use Sylament\Tests\Fixtures\Entity\SatchelCharm;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host with no table and no record of its own, carrying the record actions a test mounts
 * by name: creates over `CappedHeadline` and the key-shaped entities, creates that join the
 * first seeded owner's collection through their own `relationship()`, an edit and a view
 * bound by `recordKey` to a `Product`, and the variants each case needs. It routes the
 * `CappedHeadline` actions named `linked…` to a page, answers the `CappedHeadline` keyed
 * `PAGE` as the record of `createBesidePage`, the first `Larder`'s crocks as the
 * relationship of `createCrockByHost`, and a schema for `viewResolved`.
 */
#[AsLiveComponent(name: 'Actions:RecordActionsHost', template: '@Fixtures/actions/RecordActionsHost.html.twig')]
class RecordActionsHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $log,
        private readonly RecordingAssociationReader $reader,
    ) {
        $this->resolveRecordUsing(fn (string $key): ?Product => $this->entityManager->find(Product::class, (int) $key));
    }

    public function schemas(): array
    {
        return [];
    }

    public function createAction(): CreateAction
    {
        return CreateAction::make()
            ->recordClass(CappedHeadline::class)
            ->schema($this->headlineFields())
            ->preserveFormDataWhenCreatingAnother(['title']);
    }

    public function createLoneAction(): CreateAction
    {
        return CreateAction::make('createLone')
            ->recordClass(CappedHeadline::class)
            ->createAnother(false)
            ->schema($this->headlineFields());
    }

    public function createRelabelledAction(): CreateAction
    {
        return CreateAction::make('createRelabelled')
            ->recordClass(CappedHeadline::class)
            ->createAnotherAction(fn (Action $action): Action => $action->label('Create & add another'))
            ->schema($this->headlineFields());
    }

    public function createUsingAction(): CreateAction
    {
        return CreateAction::make('createUsing')
            ->recordClass(CappedHeadline::class)
            ->schema($this->headlineFields())
            ->mutateDataUsing(fn (array $data): array => [...$data, 'body' => 'Added by the hook'])
            ->using(function (array $data, Schema $schema, Schema $form, string $recordClass): CappedHeadline {
                $code = $data['code'] ?? null;
                $this->log->record(sprintf('using() made a %s from %s', $recordClass, implode(', ', array_keys($data))));

                $headline = new CappedHeadline(code: is_string($code) ? mb_strtoupper($code) : null, title: 'By using()', slug: 'by-using');
                $this->entityManager->persist($headline);
                $this->entityManager->flush();

                return $headline;
            });
    }

    public function createHookedAction(): CreateAction
    {
        return CreateAction::make('createHooked')
            ->recordClass(CappedHeadline::class)
            ->schema([
                TextInput::make('code')->required(),
                TextInput::make('title'),
                Hidden::make('slug'),
            ])
            ->mutateDataUsing(fn (array $data): array => [...$data, 'title' => 'Hook', 'slug' => 'hooked']);
    }

    public function createUnmappedAction(): CreateAction
    {
        return CreateAction::make('createUnmapped')
            ->recordClass(CappedHeadline::class)
            ->schema($this->headlineFields())
            ->mutateDataUsing(fn (array $data): array => [...$data, 'body' => 'Dropped', 'meta' => ['source' => 'hook']]);
    }

    public function createBesidePageAction(): CreateAction
    {
        return CreateAction::make('createBesidePage')
            ->recordClass(CappedHeadline::class)
            ->schema($this->headlineFields());
    }

    public function createWatchedAction(): CreateAction
    {
        return CreateAction::make('createWatched')
            ->recordClass(CappedHeadline::class)
            ->schema($this->watchedSchema(...));
    }

    public function createProductAction(): CreateAction
    {
        return CreateAction::make('createProduct')
            ->recordClass(Product::class)
            ->schema([TextInput::make('name')]);
    }

    public function createMooringAction(): CreateAction
    {
        return CreateAction::make('createMooring')
            ->recordClass(QuayMooring::class)
            ->schema([TextInput::make('label')]);
    }

    public function createLooseMooringAction(): CreateAction
    {
        return CreateAction::make('createLooseMooring')
            ->recordClass(QuayMooring::class)
            ->schema(fn (Schema $schema): Schema => $schema
                ->validateRecordKey(false)
                ->components([
                    TextInput::make('pier')->required(false),
                    TextInput::make('slot')->required(false),
                    TextInput::make('label'),
                ]));
    }

    public function createLocketAction(): CreateAction
    {
        return CreateAction::make('createLocket')
            ->recordClass(ClaspedLocket::class)
            ->schema([TextInput::make('label')]);
    }

    public function createBulletinAction(): CreateAction
    {
        return CreateAction::make('createBulletin')
            ->recordClass(StampedBulletin::class)
            ->schema([
                TextInput::make('scope'),
                TextInput::make('label'),
            ]);
    }

    public function createStampedBulletinAction(): CreateAction
    {
        return CreateAction::make('createStampedBulletin')
            ->recordClass(StampedBulletin::class)
            ->schema([
                TextInput::make('scope'),
                TextInput::make('stamp'),
                TextInput::make('label'),
            ]);
    }

    public function createCrockAction(): CreateAction
    {
        return CreateAction::make('createCrock')
            ->recordClass(LarderCrock::class)
            ->relationship(fn (): Relationship => new Relationship($this->firstLarder(), 'crocks'))
            ->schema($this->crockFields());
    }

    public function createCrockByHostAction(): CreateAction
    {
        return CreateAction::make('createCrockByHost')
            ->recordClass(LarderCrock::class)
            ->schema($this->crockFields());
    }

    public function createCrockPickedAction(): CreateAction
    {
        return CreateAction::make('createCrockPicked')
            ->recordClass(LarderCrock::class)
            ->relationship(fn (): Relationship => new Relationship($this->firstLarder(), 'crocks'))
            ->schema([
                Select::make('larder')->relationship(titleAttribute: 'name'),
                ...$this->crockFields(),
            ]);
    }

    public function createCharmAction(): CreateAction
    {
        return $this->charmAction('createCharm', [TextInput::make('tier')->integer(), TextInput::make('code'), TextInput::make('label')]);
    }

    public function createCharmUntieredAction(): CreateAction
    {
        return $this->charmAction('createCharmUntiered', [TextInput::make('code'), TextInput::make('label')]);
    }

    public function createCharmUncodedAction(): CreateAction
    {
        return $this->charmAction('createCharmUncoded', [TextInput::make('tier')->integer(), TextInput::make('label')]);
    }

    public function createSpareAction(): CreateAction
    {
        return CreateAction::make('createSpare')
            ->recordClass(Product::class)
            ->relationship(fn (): Relationship => new Relationship($this->first(Lighthouse::class), 'spares'))
            ->schema([TextInput::make('name')]);
    }

    public function createTerracePlantAction(): CreateAction
    {
        return $this->plantAction('createTerracePlant', Terrace::class);
    }

    public function createAllotmentPlantAction(): CreateAction
    {
        return $this->plantAction('createAllotmentPlant', Allotment::class);
    }

    public function linkedCreateAction(): CreateAction
    {
        return CreateAction::make('linkedCreate')
            ->recordClass(CappedHeadline::class)
            ->schema($this->headlineFields());
    }

    public function linkedForcedAction(): CreateAction
    {
        return CreateAction::make('linkedForced')
            ->recordClass(CappedHeadline::class)
            ->modal()
            ->schema($this->headlineFields());
    }

    public function linkedArticleAction(): CreateAction
    {
        return CreateAction::make('linkedArticle')
            ->recordClass(Article::class)
            ->schema([TextInput::make('title')]);
    }

    public function linkedEditAction(): EditAction
    {
        return EditAction::make('linkedEdit')->recordClass(CappedHeadline::class);
    }

    public function editProductAction(): EditAction
    {
        return EditAction::make('editProduct')
            ->recordTitleAttribute('name')
            ->schema([TextInput::make('name')]);
    }

    public function editUsingAction(): EditAction
    {
        return EditAction::make('editUsing')
            ->schema([TextInput::make('name')])
            ->using(function (object $record, array $data): object {
                $name = $data['name'] ?? null;
                $this->log->record(sprintf('using() was handed a %s, renamed %s', $record::class, is_string($name) ? $name : '?'));

                return $record;
            });
    }

    public function editArrayAction(): EditAction
    {
        return EditAction::make('editArray')
            ->record(['__key' => '1', 'name' => 'Lamp'])
            ->schema([TextInput::make('name')]);
    }

    public function editBareAction(): EditAction
    {
        return EditAction::make('editBare')
            ->record(fn (): ?Product => $this->entityManager->getRepository(Product::class)->findOneBy([]));
    }

    public function editUntitledAction(): EditAction
    {
        return EditAction::make('editUntitled');
    }

    public function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->recordTitleAttribute('name')
            ->schema([TextInput::make('name')]);
    }

    public function viewResolvedAction(): ViewAction
    {
        return ViewAction::make('viewResolved');
    }

    public function viewBareAction(): ViewAction
    {
        return ViewAction::make('viewBare');
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        if (str_starts_with($action->getName(), 'linked') && $action->getRecordClass() === CappedHeadline::class) {
            return "/headlines/{$action->getName()}";
        }

        return null;
    }

    public function getDefaultActionRecord(Action $action): ?object
    {
        return $action->getName() === 'createBesidePage' ? $this->entityManager->find(CappedHeadline::class, 'PAGE') : null;
    }

    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        return $action->getName() === 'createCrockByHost' ? new Relationship($this->firstLarder(), 'crocks') : null;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return $action->getName() === 'viewResolved'
            ? fn (Schema $schema): Schema => $schema->components([TextInput::make('name')])
            : null;
    }

    /**
     * @return list<TextInput>
     */
    private function headlineFields(): array
    {
        return [
            TextInput::make('code')->required(),
            TextInput::make('title'),
            TextInput::make('slug'),
        ];
    }

    /**
     * @return list<TextInput>
     */
    private function crockFields(): array
    {
        return [
            TextInput::make('shelf'),
            TextInput::make('label'),
        ];
    }

    private function watchedSchema(Schema $schema): Schema
    {
        $schema->setAssociationReader($this->reader);

        return $schema->components($this->headlineFields());
    }

    /**
     * @param list<TextInput> $fields
     */
    private function charmAction(string $name, array $fields): CreateAction
    {
        return CreateAction::make($name)
            ->recordClass(SatchelCharm::class)
            ->relationship(fn (): Relationship => new Relationship($this->first(CharmSatchel::class), 'charms'))
            ->schema($fields);
    }

    /**
     * @param class-string $ownerClass
     */
    private function plantAction(string $name, string $ownerClass): CreateAction
    {
        return CreateAction::make($name)
            ->recordClass(Plant::class)
            ->relationship(fn (): Relationship => new Relationship($this->first($ownerClass), 'plants'))
            ->schema([TextInput::make('name')]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function first(string $class): object
    {
        return $this->entityManager->getRepository($class)->findOneBy([])
            ?? throw new LogicException(sprintf('No %s was seeded.', $class));
    }

    private function firstLarder(): Larder
    {
        return $this->first(Larder::class);
    }
}
