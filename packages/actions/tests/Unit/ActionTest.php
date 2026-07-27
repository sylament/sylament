<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Error;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Actions\Tests\Fixtures\StateFieldFixture;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Utilities\Get;
use Sylament\Schemas\Components\Utilities\Set;
use Sylament\Schemas\Schema;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;

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

it('returns an empty context without a schema binding', function (): void {
    expect(Action::make('save')->getContext())->toBe([]);
});

it('emits the schema container key as the schemaComponent context', function (): void {
    $action = Action::make('save')->schemaContainer(Schema::make()->key('form'));

    expect($action->getContext())->toBe(['schemaComponent' => 'form']);
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

it('fatals when $get is requested without a schema component, as in Filament', function (): void {
    expect(fn (): mixed => Action::make('save')->evaluate(fn (Get $get): mixed => $get('title')))
        ->toThrow(Error::class);
});
