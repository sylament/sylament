<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host whose schema carries real constraints, so the shipped field
 * templates render the host error bag: `title` is required and length-capped,
 * `excerpt` is unconstrained and must therefore stay error-free.
 */
#[AsLiveComponent(name: 'ValidatedArticleForm', template: '@FormsFixtures/components/ValidatedArticleForm.html.twig')]
class ValidatedArticleForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedState = [];

    public function mount(): void
    {
        $this->fill();
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(10),
                Textarea::make('excerpt'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }
}
