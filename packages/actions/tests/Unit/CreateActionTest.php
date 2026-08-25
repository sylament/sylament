<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Twig\Components\DefaultRecordHost;
use Sylament\Schemas\State\Relationship;

it('names itself create', function (): void {
    expect(CreateAction::make()->getName())->toBe('create')
        ->and(CreateAction::getDefaultName())->toBe('create');
});

it('keeps an explicit name over the default', function (): void {
    expect(CreateAction::make('createArticle')->getName())->toBe('createArticle');
});

it('carries the create chrome', function (): void {
    $action = CreateAction::make();

    expect($action->getLabel())->toBe('Create')
        ->and($action->getSuccessNotificationTitle())->toBe('Created');
});

it('carries a grouped icon only, leaving the plain trigger iconless', function (): void {
    $action = CreateAction::make();

    expect($action->getGroupedIcon())->toBe('heroicons:plus-20-solid')
        ->and($action->getIcon())->toBeNull();
});

it('sets no url of its own, so a hostless action has none', function (): void {
    expect(CreateAction::make()->getUrl())->toBeNull();
});

it('takes an explicit url', function (): void {
    expect(CreateAction::make()->url('/articles/new')->getUrl())->toBe('/articles/new');
});

it('interpolates the record label into the trigger and the heading', function (): void {
    $action = CreateAction::make()->recordClass('App\\Entity\\BlogPost');

    expect($action->getLabel())->toBe('New blog post')
        ->and($action->getModalHeading())->toBe('Create Blog Post');
});

it('falls back to the bare verb when nothing names a record class', function (): void {
    $action = CreateAction::make();

    expect($action->getLabel())->toBe('Create')
        ->and($action->getModalHeading())->toBe('Create')
        ->and($action->getModalSubmitActionLabel())->toBe('Create');
});

it('carries the create-another footer action by default and drops it on createAnother(false)', function (): void {
    $footerActions = CreateAction::make()->getExtraModalFooterActions();

    expect($footerActions)->toHaveKey('createAnother')
        ->and($footerActions['createAnother']->getLabel())->toBe('Create & create another')
        ->and($footerActions['createAnother']->getArguments())->toBe(['another' => true])
        ->and(CreateAction::make()->createAnother(false)->getExtraModalFooterActions())->toBe([]);
});

it('hands the create-another action to a modifier closure', function (): void {
    $footerActions = CreateAction::make()
        ->createAnotherAction(fn (Action $action): Action => $action->label('Save & add another'))
        ->getExtraModalFooterActions();

    expect($footerActions['createAnother']->getLabel())->toBe('Save & add another');
});

it('clears its record after the run', function (): void {
    expect(CreateAction::make()->shouldClearRecordAfter())->toBeTrue();
});

it('takes its relationship from its own closure', function (): void {
    $relationship = new Relationship(new Note(), 'tags');

    expect(CreateAction::make()->relationship(fn (): Relationship => $relationship)->getRelationship())->toBe($relationship);
});

it('names no relationship without a closure, a table or a host', function (): void {
    expect(CreateAction::make()->getRelationship())->toBeNull();
});

it('falls through to the host hook, which a non-creating action never reaches', function (): void {
    $host = new DefaultRecordHost();

    expect($host->getAction('create')?->getRelationship())->toBe($host->relationship)
        ->and($host->getDefaultActionRelationship(Action::make('ping')))->toBeNull();
});
