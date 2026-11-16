<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;

it('leaves out the native required and length checks of a control a container may hide from view', function (Closure $container): void {
    expect($this->renderSchemaComponent($container(TextInput::make('code')->required()->maxLength(5))))
        ->toContainElement('input[name="code"]:not([required]):not([maxlength])');
})->with([
    'a collapsible section' => [static fn (TextInput $field): Section => Section::make('Details')->collapsible()->components([$field])],
    'a group inside a collapsible section' => [static fn (TextInput $field): Section => Section::make('Details')->collapsible()->components([Group::make([$field])])],
    'a tab' => [static fn (TextInput $field): Tabs => Tabs::make('Tabs')->tabs([Tab::make('Details')->components([$field])])],
    'a wizard step' => [static fn (TextInput $field): Wizard => Wizard::make([Step::make('Details')->components([$field])])],
]);

it('keeps the native required and length checks of a control nothing may hide', function (): void {
    expect($this->renderSchemaComponent(Section::make('Details')->components([TextInput::make('code')->required()->maxLength(5)])))
        ->toContainElement('input[name="code"][required][maxlength="5"]');
});
