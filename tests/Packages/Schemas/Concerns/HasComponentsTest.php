<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Twig\Markup;

describe('components', function (): void {
    it('resolves children given as a closure when it renders, a string as text and markup as HTML', function (): void {
        $schema = Schema::make()->components(static fn (): array => [
            'Plain words',
            new Markup('<em>Marked up</em>', 'UTF-8'),
        ]);

        expect($this->renderViewComponent($schema))
            ->toContainElement(Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Plain words')
            ->toContainElement(Selectors::COMPONENT_CELL . ' em', 'Marked up');
    });
});

describe('getFlatComponents', function (): void {
    it('lists every component in order, its nested ones after it, and each action and action group without what a group holds', function (): void {
        $nested = Text::make('Nested');
        $section = Section::make('Billing')->components([$nested]);
        $archive = Action::make('archive');
        $group = ActionGroup::make([Action::make('export')]);
        $concealed = Text::make('Concealed')->hidden();

        $schema = Schema::make()->components([$section, $archive, $group, $concealed]);

        expect($schema->getFlatComponents())->toBe([$section, $nested, $archive, $group, $concealed])
            ->and($schema->getFlatComponents(withActions: false, withHidden: false))->toBe([$section, $nested]);
    });
});

describe('getComponent', function (): void {
    it('finds a component by its key under the schema, by its absolute key or by a test, and a hidden one only when asked', function (): void {
        $city = TextInput::make('city');
        $zip = TextInput::make('zip')->hidden();
        $section = Section::make('Billing')->key('billing')->components([$city, $zip]);

        $schema = Schema::make()->key('form')->components([$section, Action::make('archive')]);

        expect($schema->getComponent('billing.city'))->toBe($city)
            ->and($schema->getComponent('form.billing.city', isAbsoluteKey: true))->toBe($city)
            ->and($schema->getComponent(static fn (object $component): bool => $component instanceof Section))->toBe($section)
            ->and($schema->getComponent('billing.zip'))->toBeNull()
            ->and($schema->getComponent('billing.zip', withHidden: true))->toBe($zip);
    });
});

describe('getComponentByStatePath', function (): void {
    it('finds the component an absolute state path leads to, unless it sits inside a component it is told to skip', function (): void {
        $city = TextInput::make('city');
        $address = Group::make([$city])->statePath('address');

        $schema = Schema::make()->statePath('data')->components([$address]);

        expect($schema->getComponentByStatePath('data.address.city'))->toBe($city)
            ->and($schema->getComponentByStatePath('data.address'))->toBe($address)
            ->and($schema->getComponentByStatePath('data.address.city', [$address]))->toBeNull();
    });
});

describe('getAction', function (): void {
    it('finds an action among its children, in an action group, in a keyed schema the nested key leads to and on the component the key names, skipping hidden components', function (): void {
        $archive = Action::make('archive');
        $export = Action::make('export');
        $deep = Action::make('deep');
        $clear = Action::make('clear');

        $schema = Schema::make()->components([
            $archive,
            ActionGroup::make([$export]),
            Section::make('Billing')->key('billing')->components(Schema::make()->key('inner')->components([$deep])),
            TextInput::make('code')->suffixAction($clear),
            Section::make('Concealed')->hidden()->components([Action::make('concealed')]),
        ]);

        expect($schema->getAction('archive'))->toBe($archive)
            ->and($schema->getAction('export'))->toBe($export)
            ->and($schema->getAction('deep', 'billing.inner'))->toBe($deep)
            ->and($schema->getAction('deep'))->toBeNull()
            ->and($schema->getAction('clear', 'code'))->toBe($clear)
            ->and($schema->getAction('clear'))->toBeNull()
            ->and($schema->getAction('concealed'))->toBeNull();
    });
});
