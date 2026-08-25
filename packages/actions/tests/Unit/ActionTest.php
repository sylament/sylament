<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use ArrayObject;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use stdClass;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\Tests\Fixtures\Components\ItemActionsContainer;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Actions\Tests\Fixtures\DefaultNamedActionFixture;
use Sylament\Actions\Tests\Fixtures\StateFieldFixture;
use Sylament\Actions\Tests\Fixtures\Twig\Components\DefaultRecordHost;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;
use Sylament\Tables\Table;

it('exposes the name passed to make()', function (): void {
    expect(Action::make('delete')->getName())->toBe('delete');
});

it('throws when the name is blank', function (): void {
    expect(fn (): string => Action::make()->getName())->toThrow(LogicException::class);
});

it('round-trips the action closure', function (): void {
    $closure = fn (): string => 'ran';

    expect(Action::make('delete')->action($closure)->getAction())->toBe($closure);
});

it('round-trips a string action as a live-action click handler', function (): void {
    $action = Action::make('submit')->action('callMountedAction');

    expect($action->getAction())->toBe('callMountedAction')
        ->and($action->getClientClickHandler())->toBe('callMountedAction');
});

it('returns the string from call() on a string action', function (): void {
    expect(Action::make('submit')->action('callMountedAction')->call())->toBe('callMountedAction');
});

it('has no client click handler for closure actions', function (): void {
    expect(Action::make('delete')->action(fn (): null => null)->getClientClickHandler())->toBeNull()
        ->and(Action::make('delete')->getClientClickHandler())->toBeNull();
});

/*
 * The gate the five trigger templates read before wiring any click of their own.
 * Its two default arms are what keep a url-carrying or form-submitting trigger
 * from also firing `mountAction`; the explicit override is what lets a caller
 * render an action with no click at all and supply the behaviour from the markup
 * around it, as `CheckboxList`'s bulk-toggle links do.
 */
it('gates the live-component click on the url, the form submission and an explicit override', function (): void {
    expect(Action::make('delete')->isLiveComponentClickHandlerEnabled())->toBeTrue()
        ->and(Action::make('delete')->url('/somewhere')->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and(Action::make('save')->submit('callMountedAction')->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and(Action::make('delete')->liveComponentClickHandlerEnabled(false)->isLiveComponentClickHandlerEnabled())
        ->toBeFalse()
        ->and(Action::make('delete')->liveComponentClickHandlerEnabled(fn (): bool => false)->isLiveComponentClickHandlerEnabled())
        ->toBeFalse()
        ->and(Action::make('delete')->url('/somewhere')->liveComponentClickHandlerEnabled()->isLiveComponentClickHandlerEnabled())
        ->toBeTrue();
});

it('names callMountedAction as the live-component call target', function (): void {
    expect(Action::make('submit')->getLiveComponentCallMountedActionName())->toBe('callMountedAction');
});

it('does not require confirmation by default', function (): void {
    expect(Action::make('delete')->isConfirmationRequired())->toBeFalse();
});

it('requires confirmation once requested', function (): void {
    expect(Action::make('delete')->requiresConfirmation()->isConfirmationRequired())->toBeTrue();
});

it('calls the action closure, injecting the record and arguments', function (): void {
    $seen = null;
    $action = Action::make('delete')
        ->record('Ada')
        ->arguments(['soft' => true])
        ->action(function (mixed $record, array $arguments) use (&$seen): void {
            $seen = [$record, $arguments];
        });

    $action->call();

    expect($seen)->toBe(['Ada', ['soft' => true]]);
});

it('defaults the status to Success and flips to Failure on failure()', function (): void {
    expect(Action::make('delete')->getStatus())->toBe(ActionStatus::Success);

    $action = Action::make('delete');
    $action->failure();

    expect($action->getStatus())->toBe(ActionStatus::Failure);
});

it('throws Cancel from cancel() and Halt from halt()', function (): void {
    expect(fn (): mixed => Action::make('delete')->call())->not->toThrow(Halt::class);
    expect(fn () => Action::make('delete')->cancel())->toThrow(Cancel::class);
    expect(fn () => Action::make('delete')->halt())->toThrow(Halt::class);
});

it('defaults the thrown Cancel and Halt to committing the database transaction', function (): void {
    $cancel = null;
    $halt = null;

    try {
        Action::make('delete')->cancel();
    } catch (Cancel $exception) {
        $cancel = $exception;
    }

    try {
        Action::make('delete')->halt();
    } catch (Halt $exception) {
        $halt = $exception;
    }

    expect($cancel?->shouldRollbackDatabaseTransaction())->toBeFalse()
        ->and($halt?->shouldRollbackDatabaseTransaction())->toBeFalse();
});

it('flags the thrown Cancel and Halt for rollback when asked', function (): void {
    $cancel = null;
    $halt = null;

    try {
        Action::make('delete')->cancel(true);
    } catch (Cancel $exception) {
        $cancel = $exception;
    }

    try {
        Action::make('delete')->halt(true);
    } catch (Halt $exception) {
        $halt = $exception;
    }

    expect($cancel?->shouldRollbackDatabaseTransaction())->toBeTrue()
        ->and($halt?->shouldRollbackDatabaseTransaction())->toBeTrue();
});

it('returns an empty context without a schema binding', function (): void {
    expect(Action::make('save')->getContext())->toBe([]);
});

it('emits the schema container key as the schemaComponent context', function (): void {
    $action = Action::make('save')->schemaContainer(Schema::make()->key('form'));

    expect($action->getContext())->toBe(['schemaComponent' => 'form']);
});

it('strips the schemaComponent context once a parent action is set, even though the binding alone would emit it', function (): void {
    $action = Action::make('save')
        ->schemaContainer(Schema::make()->key('form'))
        ->parentAction(Action::make('edit'));

    expect($action->getContext())->toBe([]);
});

it('emits the schema component key as the schemaComponent context when only a component is bound', function (): void {
    $component = new Component()->key('box')->container(Schema::make()->key('form'));

    $action = Action::make('save')->schemaComponent($component);

    expect($action->getContext())->toBe(['schemaComponent' => 'form.box']);
});

it('returns an empty context when the bound schema container has no key', function (): void {
    $action = Action::make('save')->schemaContainer(Schema::make());

    expect($action->getContext())->toBe([]);
});

it('emits the resolved record key as the recordKey context', function (): void {
    $action = Action::make('edit')->record(new class {
        public int $id = 7;
    });

    expect($action->getContext())->toBe(['recordKey' => '7']);
});

it('emits no recordKey for a record whose key cannot be resolved', function (): void {
    $action = Action::make('edit')->record(new class {});

    expect($action->getContext())->toBe([]);
});

it('keeps only recordKey on a nested frame', function (): void {
    $action = Action::make('child')
        ->record(new class {
            public int $id = 7;
        })
        ->schemaContainer(Schema::make()->key('form'))
        ->parentAction(Action::make('parent'));

    expect($action->getContext())->toBe(['recordKey' => '7']);
});

it('carries no bulk flag by default and takes one from bulk()', function (): void {
    expect(Action::make('delete')->isBulk())->toBeFalse()
        ->and(Action::make('delete')->bulk()->isBulk())->toBeTrue()
        ->and(Action::make('delete')->bulk(false)->isBulk())->toBeFalse()
        ->and(Action::make('delete')->bulk(fn (): bool => true)->isBulk())->toBeTrue();
});

it('emits no bulk context key for a bulk action with no table', function (): void {
    expect(Action::make('delete')->bulk()->getContext())->toBe([]);
});

it('clones an action independently of the original', function (): void {
    $action = Action::make('edit');
    $clone = $action->getClone()->record(['__key' => '1']);

    expect($clone)->not->toBe($action)
        ->and($clone->getRecord())->toBe(['__key' => '1'])
        ->and($action->getRecord())->toBeNull();
});

it('injects the schema container as $schema', function (): void {
    $schema = Schema::make();

    $action = Action::make('save')->schemaContainer($schema);

    expect($action->evaluate(fn ($schema): Schema => $schema))->toBe($schema);
});

it('injects the schema component as $component', function (): void {
    $section = Section::make('Box');

    $action = Action::make('save')->schemaComponent($section);

    expect($action->evaluate(fn ($component): Section => $component))->toBe($section);
});

it('injects $get and $set utilities scoped to the schema component', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada', 'subtitle' => 'Bo'];

    $title = StateFieldFixture::make('title');
    $subtitle = StateFieldFixture::make('subtitle');
    $schema = Schema::make($host)->statePath('data')->components([$title, $subtitle]);
    $schema->getComponents();

    $action = Action::make('save')->schemaComponent($subtitle);

    expect($action->evaluate(fn ($get): mixed => $get('title')))->toBe('Ada');

    $action->evaluate(function (Set $set): void {
        $set('title', 'Grace');
    });

    expect($host->data['title'])->toBe('Grace');
});

it('injects the $get utility by type under any parameter name', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada', 'subtitle' => 'Bo'];

    $title = StateFieldFixture::make('title');
    $subtitle = StateFieldFixture::make('subtitle');
    Schema::make($host)->statePath('data')->components([$title, $subtitle])->getComponents();

    $action = Action::make('save')->schemaComponent($subtitle);

    expect($action->evaluate(fn (Get $reader): mixed => $reader('title')))->toBe('Ada');
});

it('injects the $set utility by type under any parameter name', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada'];

    $title = StateFieldFixture::make('title');
    Schema::make($host)->statePath('data')->components([$title])->getComponents();

    $action = Action::make('save')->schemaComponent($title);

    $action->evaluate(function (Set $writer): void {
        $writer('title', 'Grace');
    });

    expect($host->data['title'])->toBe('Grace');
});

it('lets $get and $set reach into the bound schema component\'s own child schema, disabling the self-skip', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['address' => ['city' => 1]];

    $group = StateFieldFixture::make('address')->components([
        StateFieldFixture::make('city')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
    ]);
    Schema::make($host)->statePath('data')->components([$group])->getComponents();

    $action = Action::make('save')->schemaComponent($group);

    expect($action->evaluate(fn ($get): mixed => $get('/data.address.city')))->toBe(true);

    $action->evaluate(fn (Set $set): mixed => $set('/data.address.city', false));

    expect($host->data['address']['city'])->toBe(0);
});

it('injects $state from the nearest state-path-bearing parent component', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada'];

    $title = StateFieldFixture::make('title');
    Schema::make($host)->statePath('data')->components([$title])->getComponents();

    $inner = Schema::make($host)->parentComponent($title);
    $action = Action::make('save')->schemaContainer($inner);

    expect($action->evaluate(fn ($state): mixed => $state))->toBe('Ada');
});

it('injects $state from the schema component itself when no container is bound', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada'];

    $title = StateFieldFixture::make('title');
    Schema::make($host)->statePath('data')->components([$title])->getComponents();

    $action = Action::make('save')->schemaComponent($title);

    expect($action->evaluate(fn ($state): mixed => $state))->toBe('Ada');
});

it('injects $schemaState from the first state-path-bearing schema container', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['title' => 'Ada', 'subtitle' => 'Bo'];

    $schema = Schema::make($host)->statePath('data')->components([
        StateFieldFixture::make('title'),
        StateFieldFixture::make('subtitle'),
    ]);

    $action = Action::make('save')->schemaContainer($schema);

    expect($action->evaluate(fn ($schemaState): mixed => $schemaState))
        ->toBe(['title' => 'Ada', 'subtitle' => 'Bo']);
});

it('injects $data from getData()', function (): void {
    $action = Action::make('save')->data(['title' => 'Ada']);

    expect($action->evaluate(fn ($data): array => $data))->toBe(['title' => 'Ada']);
});

it('throws a named exception when $get is requested without a schema component', function (): void {
    expect(fn (): mixed => Action::make('save')->evaluate(fn (Get $get): mixed => $get('title')))
        ->toThrow(LogicException::class, 'has no schema component');
});

it('defaults to the button view', function (): void {
    $action = Action::make('delete');

    expect($action->getTemplate())->toBe(Action::BUTTON_TEMPLATE)
        ->and($action->isButton())->toBeTrue()
        ->and($action->isBadge())->toBeFalse()
        ->and($action->isIconButton())->toBeFalse()
        ->and($action->isLink())->toBeFalse();
});

it('switches to the badge view', function (): void {
    $action = Action::make('delete')->badge();

    expect($action->getTemplate())->toBe(Action::BADGE_TEMPLATE)
        ->and($action->isBadge())->toBeTrue()
        ->and($action->isButton())->toBeFalse();
});

it('switches to the button view', function (): void {
    expect(Action::make('delete')->link()->button()->getTemplate())->toBe(Action::BUTTON_TEMPLATE);
});

it('switches to the grouped view', function (): void {
    expect(Action::make('delete')->grouped()->getTemplate())->toBe(Action::GROUPED_TEMPLATE);
});

it('switches to the icon button view', function (): void {
    $action = Action::make('delete')->iconButton();

    expect($action->getTemplate())->toBe(Action::ICON_BUTTON_TEMPLATE)
        ->and($action->isIconButton())->toBeTrue()
        ->and($action->isButton())->toBeFalse();
});

it('switches to the link view', function (): void {
    $action = Action::make('delete')->link();

    expect($action->getTemplate())->toBe(Action::LINK_TEMPLATE)
        ->and($action->isLink())->toBeTrue()
        ->and($action->isButton())->toBeFalse();
});

it('keeps an explicit view over a pushed default template', function (): void {
    expect(Action::make('delete')->button()->defaultTemplate(Action::GROUPED_TEMPLATE)->getTemplate())
        ->toBe(Action::BUTTON_TEMPLATE);
});

it('round-trips the parent action', function (): void {
    $parent = Action::make('edit');
    $child = Action::make('confirm')->parentAction($parent);

    expect($child->getParentAction())->toBe($parent)
        ->and(Action::make('confirm')->getParentAction())->toBeNull();
});

it('falls back to getDefaultName() when make() is given no name', function (): void {
    expect(DefaultNamedActionFixture::make()->getName())->toBe('publish');
});

it('prefers an explicit name over getDefaultName()', function (): void {
    expect(DefaultNamedActionFixture::make('archive')->getName())->toBe('archive');
});

it('returns null from getDefaultName() by default', function (): void {
    expect(Action::getDefaultName())->toBeNull();
});

it('injects the item state as $schemaState for an action invoked with an item argument', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['rows' => []];

    $container = ItemActionsContainer::make('rows')->extraItemActions([Action::make('inspect')]);
    $schema = Schema::make($host)->statePath('data')->components([$container]);
    $schema->getComponents();

    $action = $container->getAction('inspect')?->schemaContainer($schema);

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->arguments(['item' => 'k1'])->evaluate(fn ($schemaState): mixed => $schemaState))->toBe(['key' => 'k1', 'title' => 'Item k1'])
        ->and($action->arguments(null)->evaluate(fn ($schemaState): mixed => $schemaState))->toBe(['rows' => []]);
});

it('falls through to the container walk when the item argument is blank', function (): void {
    $host = new BothContractsHostFixture();
    $host->data = ['rows' => []];

    $container = ItemActionsContainer::make('rows')->extraItemActions([Action::make('inspect')]);
    $schema = Schema::make($host)->statePath('data')->components([$container]);
    $schema->getComponents();

    $action = $container->getAction('inspect')?->schemaContainer($schema);

    expect($action?->arguments(['item' => ''])->evaluate(fn ($schemaState): mixed => $schemaState))
        ->toBe(['rows' => []]);
});

/*
 * The four selected-record injection arms are gated on `hasCanAccessSelectedRecords()`,
 * not on the evaluated flag: an ordinary action naming a `$records` or
 * `$recordsQuery` parameter — or type-hinting a `QueryBuilder` — must fall
 * through to the parent resolution rather than throw the access gate at a
 * caller who never asked for a selection.
 */
it('leaves a $records parameter to the parent resolution on an action that accesses no selected records', function (): void {
    expect(Action::make('archive')->evaluate(fn (array $records = ['fallback']): array => $records))
        ->toBe(['fallback']);
});

it('leaves a $recordsQuery parameter to the parent resolution on an action that accesses no selected records', function (): void {
    expect(Action::make('archive')->evaluate(fn (?QueryBuilder $recordsQuery = null): ?QueryBuilder => $recordsQuery))
        ->toBeNull();
});

it('leaves a QueryBuilder-typed parameter to the parent resolution on an action that accesses no selected records', function (): void {
    expect(Action::make('archive')->evaluate(fn (?QueryBuilder $selection = null): ?QueryBuilder => $selection))
        ->toBeNull();
});

/*
 * The `call()` finally reaches its host through the nullable accessor, so a
 * detached action carrying the deselect flag runs its closure and returns
 * instead of fataling on a host that is not there.
 */
it('runs a detached action that deselects after completion without reaching for a host', function (): void {
    expect(Action::make('archive')->deselectRecordsAfterCompletion()->action(fn (): string => 'ran')->call())
        ->toBe('ran');
});

it('walks the record chain to its schema container and component before the host', function (): void {
    $schema = Schema::make()->record($record = new stdClass());
    $action = Action::make('x')->schemaContainer($schema);

    expect($action->getRecord())->toBe($record)
        ->and($action->getRecordClass())->toBe(stdClass::class);
});

it('injects the record class into a closure as recordClass', function (): void {
    $action = Action::make('x')->recordClass(stdClass::class);

    expect($action->evaluate(fn (string $recordClass): string => $recordClass))->toBe(stdClass::class);
});

it('keeps a host-default record out of its context', function (): void {
    $host = new DefaultRecordHost();
    $action = Action::make('x')->liveComponent($host);

    expect($action->getRecord())->toBe($host->record)
        ->and($action->getContext())->not->toHaveKey('recordKey');
});

it('clears its record after the run only when it says so', function (): void {
    $action = Action::make('x')->record($record = new stdClass());

    $action->clearRecordAfter();

    expect($action->getRecord())->toBe($record)
        ->and($action->shouldClearRecordAfter())->toBeFalse();
});

it('stops at its own record when the record class does not match, rather than walking on', function (): void {
    $action = Action::make('x')->record(new stdClass())->recordClass(DateTimeImmutable::class);

    ActionGroup::make([$action])->record(new DateTimeImmutable());

    expect($action->getRecord())->toBeNull();
});

it('walks the record chain to its schema component', function (): void {
    $action = Action::make('x')->schemaComponent(Text::make('t')->record($record = new stdClass()));

    expect($action->getRecord())->toBe($record)
        ->and($action->getRecordClass())->toBe(stdClass::class);
});

it('derives the record label from its own class with no host to ask', function (): void {
    expect(Action::make('x')->getRecordLabel())->toBeNull()
        ->and(Action::make('x')->recordClass('App\\Entity\\BlogPost')->getRecordLabel())->toBe('blog post');
});

it('falls to the record label when nothing supplies a title', function (): void {
    expect(Action::make('x')->recordClass('App\\Entity\\BlogPost')->getRecordTitle())->toBe('blog post');
});

it('stops at an own record object even when it is empty', function (): void {
    $empty = new ArrayObject();
    $action = Action::make('x')->record($empty);

    ActionGroup::make([$action])->record(new DateTimeImmutable());

    expect($action->getRecord())->toBe($empty);

    $mismatched = Action::make('y')->record(new ArrayObject())->recordClass(DateTimeImmutable::class);

    ActionGroup::make([$mismatched])->record(new DateTimeImmutable());

    expect($mismatched->getRecord())->toBeNull();
});

it('answers null for the table injections on a table-less action', function (): void {
    $action = Action::make('x');

    expect($action->evaluate(fn (?Table $table): ?Table => $table))->toBeNull()
        ->and($action->evaluate(fn ($table): mixed => $table))->toBeNull();
});

it('reaches an association reader outside the container', function (): void {
    $reader = Action::make('x')->getAssociationReader();

    expect($reader)->toBeInstanceOf(AssociationReader::class)
        ->and($reader->hasEntityManager())->toBeFalse();
});
