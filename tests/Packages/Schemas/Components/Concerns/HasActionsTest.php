<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Text;

describe('registration', function (): void {
    it('answers its main action by a blank name, stamped with itself, before anything else read its actions', function (): void {
        $text = Text::make('Total')->action($open = Action::make('open'));

        expect($text->getAction())->toBe($open)
            ->and($open->getSchemaComponent())->toBe($text);
    });

    it('finds each action it registers by name, stamped with itself, and keeps them when its main action is cleared', function (): void {
        $text = Text::make('Total')
            ->registerActions([Action::make('edit')])
            ->action(Action::make('open'))
            ->action(null);

        expect($text->getAction())->toBeNull()
            ->and($text->getAction('edit')?->getSchemaComponent())->toBe($text)
            ->and($text->getAction('open')?->getSchemaComponent())->toBe($text)
            ->and($text->getAction('missing'))->toBeNull();
    });

    it('stamps a group it prepares with itself, so the actions of the group answer it', function (): void {
        $text = Text::make('Total');

        $text->prepareActionGroup(ActionGroup::make([$export = Action::make('export')]));

        expect($export->getSchemaComponent())->toBe($text);
    });
});

describe('modal actions', function (): void {
    it('descends into the modal actions of an action by a dotted or a listed path, and answers nothing for a miss on the way', function (): void {
        $text = Text::make('Total')->registerActions([
            Action::make('edit')->registerModalActions([$confirm = Action::make('confirm')]),
        ]);

        expect($text->getAction('edit.confirm'))->toBe($confirm)
            ->and($text->getAction(['edit', 'confirm']))->toBe($confirm)
            ->and($text->getAction('edit.missing'))->toBeNull()
            ->and($text->getAction('missing.confirm'))->toBeNull();
    });
});

describe('cache', function (): void {
    it('keeps the actions it read until it is cached again, the last of a name winning and a closure giving several or none', function (): void {
        $text = Text::make('Total')->registerActions([
            Action::make('edit')->label('First'),
            static fn (): array => [Action::make('copy'), Action::make('edit')->label('Second')],
            static fn (): null => null,
        ]);

        expect(array_keys($text->getActions()))->toBe(['edit', 'copy'])
            ->and($text->getAction('edit')?->getLabel())->toBe('Second');

        $text->registerActions([Action::make('print')]);

        expect($text->hasAction('print'))->toBeFalse()
            ->and(array_keys($text->cacheActions()))->toBe(['edit', 'copy', 'print'])
            ->and($text->hasAction('print'))->toBeTrue();
    });
});

describe('merging', function (): void {
    it("finds a field's prefix and suffix actions by name, an action it registers under the same name winning", function (): void {
        $field = TextInput::make('code')
            ->prefixAction($clear = Action::make('clear'))
            ->suffixAction(Action::make('copy'))
            ->registerActions([$registered = Action::make('copy')->label('Registered')]);

        expect($field->getAction('clear'))->toBe($clear)
            ->and($field->getAction('copy'))->toBe($registered);
    });

    it("finds a repeater's extra item actions by name, an action it registers under the same name winning", function (): void {
        $repeater = Repeater::make('lines')
            ->extraItemActions([$duplicate = Action::make('duplicate'), Action::make('copy')])
            ->registerActions([$registered = Action::make('copy')->label('Registered')]);

        expect($repeater->getAction('duplicate'))->toBe($duplicate)
            ->and($repeater->getAction('copy'))->toBe($registered);
    });
});
