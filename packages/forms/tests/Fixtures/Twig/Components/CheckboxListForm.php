<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\CheckboxList;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host carrying one `CheckboxList`, so its binding can be
 * driven through a real round-trip: every checkbox shares one state path holding
 * the list each checked option is a member of.
 */
#[AsLiveComponent(name: 'CheckboxListForm', template: '@FormsFixtures/components/CheckboxListForm.html.twig')]
class CheckboxListForm implements InteractsWithSchemas
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
                CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS']),
            ]);
    }
}
