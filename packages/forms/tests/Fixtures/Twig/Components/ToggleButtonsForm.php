<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\ToggleButtons;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host carrying both multiplicities of `ToggleButtons`, so
 * each binding can be driven through a real round-trip: the single field's radio
 * inputs share one path and hold one value, while the multiple field's checkbox
 * inputs share a path holding a list every checked option is a member of.
 */
#[AsLiveComponent(name: 'ToggleButtonsForm', template: '@FormsFixtures/components/ToggleButtonsForm.html.twig')]
class ToggleButtonsForm implements InteractsWithSchemas
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
                ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
                ToggleButtons::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B']),
            ]);
    }
}
