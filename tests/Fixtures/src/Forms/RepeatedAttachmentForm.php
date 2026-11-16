<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Components\Repeater;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form holding one repeater item with a file field, whose renders take a state snapshot per
 * item; `save()` records the form's state in the request's {@see HookLog}, as
 * {@see FileUploadFixtures::savedState()} reads it.
 */
#[AsLiveComponent(name: 'Forms:RepeatedAttachmentForm', template: '@Fixtures/forms/RepeatedAttachmentForm.html.twig')]
class RepeatedAttachmentForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function mount(): void
    {
        $this->getSchema('form')->fill(['items' => [['attachment' => []]]]);
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('items')->addable(false)->deletable(false)->reorderable(false)->components([
                    FileUpload::make('attachment'),
                ]),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->hookLog->record(FileUploadFixtures::SAVED . json_encode($this->getSchema('form')->getState(), JSON_THROW_ON_ERROR));
    }
}
