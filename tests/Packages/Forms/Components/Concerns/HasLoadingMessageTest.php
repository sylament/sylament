<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;

use function Symfony\Component\Translation\t;

it('hands the controller the loading message, Loading... unless the field sets one', function (): void {
    expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)), 'loadingMessage'))->toBe('Loading...')
        ->and($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->loadingMessage('Fetching plants...')), 'loadingMessage'))->toBe('Fetching plants...');
});

it('translates a loading message given as a message', function (): void {
    expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->loadingMessage(t('select.loading', [], 'fixtures-forms'))), 'loadingMessage'))->toBe('Fetching plants (translated)');
});
