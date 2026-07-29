<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'ValidationHost', template: '@SchemasFixtures/components/ValidationHost.html.twig')]
class ValidationHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /** @var array<string, mixed> */
    #[LiveProp(writable: true)]
    public array $data = [];

    /** @var array<string, mixed> */
    #[LiveProp]
    public array $savedState = [];

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                ConstrainedField::make('title')->required(),
                ConstrainedField::make('slug')
                    ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('title', (string) $state)),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }
}
