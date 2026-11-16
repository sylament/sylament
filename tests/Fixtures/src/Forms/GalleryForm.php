<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\FileUpload;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form of multiple file fields: a required, reorderable `gallery` of one or two files whose
 * names are kept at `galleryNames` and whose items open at `/open/<path>` and download at a
 * `javascript:` URL, a twin `closed` whose two URL callbacks are set but whose toggles are off,
 * a `downloads` one whose items download at `/download/<path>`, a `described` one whose file
 * information comes from a callback, a `fixed` one that is not reorderable, a `capped` one of at
 * most one file, and a `brief` one whose every path is at most five characters. `mount()` fills
 * the form from the record it is given; `save()` validates it.
 */
#[AsLiveComponent(name: 'Forms:GalleryForm', template: '@Fixtures/forms/GalleryForm.html.twig')]
class GalleryForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * @param array<string, mixed> $record
     */
    public function mount(array $record = []): void
    {
        $this->getSchema('form')->fill($record);
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
                FileUpload::make('gallery')
                    ->label('Gallery')
                    ->multiple()
                    ->reorderable()
                    ->required()
                    ->minFiles(1)
                    ->maxFiles(2)
                    ->storeFileNamesIn('galleryNames')
                    ->openable()
                    ->downloadable()
                    ->getOpenableFileUrlUsing(static fn (string $file): string => '/open/' . $file)
                    ->getDownloadableFileUrlUsing(static fn (): string => 'javascript:alert(1)'),
                FileUpload::make('closed')
                    ->multiple()
                    ->getOpenableFileUrlUsing(static fn (string $file): string => '/open/' . $file)
                    ->getDownloadableFileUrlUsing(static fn (string $file): string => '/download/' . $file),
                FileUpload::make('downloads')
                    ->multiple()
                    ->downloadable()
                    ->getDownloadableFileUrlUsing(static fn (string $file): string => '/download/' . $file),
                FileUpload::make('described')
                    ->multiple()
                    ->getUploadedFileUsing(static fn (string $file, array $storedFileNames): array => ['name' => 'Described ' . $file, 'size' => count($storedFileNames), 'type' => 'text/plain', 'url' => 'javascript:alert(1)']),
                FileUpload::make('fixed')->multiple(),
                FileUpload::make('capped')->multiple()->maxFiles(1),
                FileUpload::make('brief')->multiple()->nestedRecursiveConstraint(new Length(max: 5)),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->getSchema('form')->getState();
    }
}
