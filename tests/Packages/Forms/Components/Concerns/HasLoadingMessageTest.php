<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;

it('hands the controller the loading message, Loading... unless the field sets one', function (): void {
    expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)), 'loadingMessage'))->toBe('Loading...')
        ->and($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->loadingMessage('Fetching plants...')), 'loadingMessage'))->toBe('Fetching plants...');
});
