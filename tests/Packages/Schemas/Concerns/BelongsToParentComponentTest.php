<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('renders as the root schema of its host once detached from the component around it', function (): void {
    $section = Section::make('Billing')->components([Text::make('a')]);
    $this->renderSchemaComponent($section);

    $schema = $section->getChildSchema() ?? throw new LogicException('A section with components has a child schema.');

    expect($this->renderViewComponent($schema))->toContainElement(Selectors::SCHEMA . ':not([data-controller]) ' . Selectors::TEXT, 'a')
        ->and($this->renderViewComponent($schema->parentComponent(null)))->toContainElement(Selectors::ROOT_SCHEMA . ' ' . Selectors::TEXT, 'a');
});
