<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;

it('heads its sections from the root heading level it is given, each nested one a level deeper', function (): void {
    $schema = Schema::make()->rootHeadingLevel(4)->components([
        Section::make('Customer')->components([Section::make('Address')->components([Text::make('a')])]),
    ]);

    expect($this->renderViewComponent($schema))
        ->toContainElement('h4', 'Customer')
        ->toContainElement('h5', 'Address');
});
