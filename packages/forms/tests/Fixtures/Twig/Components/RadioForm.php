<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\Radio;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host carrying a single `Radio`, so the per-option
 * `data-model` binding can be driven through a real round-trip: the client writes
 * the clicked option's `value` attribute into the one state path every input
 * shares, and the re-render must move `checked` onto that option.
 */
#[AsLiveComponent(name: 'RadioForm', template: '@FormsFixtures/components/RadioForm.html.twig')]
class RadioForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $data = [];

    public function mount(): void
    {
        $this->fill();
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
            ]);
    }
}
