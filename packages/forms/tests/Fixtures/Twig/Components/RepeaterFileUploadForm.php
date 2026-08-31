<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Components\Repeater;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A file field inside a repeater item, which is the one arrangement that renders a *read* of the
 * item's dehydrated state on every request: `Repeater::getItemLabel()` computes its `state`
 * injection eagerly, whether or not an `itemLabel()` was declared. The items are seeded through
 * `$data` rather than the add action, so the fixture needs no action host.
 */
#[AsLiveComponent(name: 'RepeaterFileUploadForm', template: '@FormsFixtures/components/FileUploadForm.html.twig')]
class RepeaterFileUploadForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The bag `getState()` assembles on `save`, kept as a prop so it survives the round-trip.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedState = [];

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('shots')
                    ->components([
                        FileUpload::make('file')->disk('test.storage')->directory('gallery'),
                    ]),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }
}
