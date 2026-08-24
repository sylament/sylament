<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Tests\Fixtures\Records\Gallery;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * The record-bound half: a `Gallery` rebuilt from `$originalCover` on every request, which is what
 * makes the tampering gate's "the record's current value" real across a round-trip — the record
 * itself cannot be a prop, but the value it is seeded from can.
 */
#[AsLiveComponent(name: 'FileUploadRecordForm', template: '@FormsFixtures/components/FileUploadForm.html.twig')]
class FileUploadRecordForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public ?string $originalCover = null;

    #[LiveProp]
    public ?string $savedCover = null;

    #[LiveProp]
    public bool $preventsFilePathTampering = true;

    private ?Gallery $record = null;

    /**
     * After the props are set, not in `mount()` — the record this fills from is built from
     * `$originalCover`, which `ComponentFactory` assigns only once `mount()` has returned.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PostMount]
    public function fillFromRecord(array $data): array
    {
        $this->fill($this->getRecord());

        return $data;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record($this->getRecord())
            ->components([
                FileUpload::make('coverImage')
                    ->disk('test.storage')
                    ->directory('covers')
                    ->preventFilePathTampering($this->preventsFilePathTampering),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $record = $this->getSchema()->getData();

        $this->savedCover = $record instanceof Gallery ? $record->coverImage : null;
    }

    public function getRecord(): Gallery
    {
        if (! $this->record instanceof Gallery) {
            $this->record = new Gallery();
            $this->record->coverImage = $this->originalCover;
        }

        return $this->record;
    }
}
