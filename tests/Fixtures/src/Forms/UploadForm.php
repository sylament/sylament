<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Uploads\TemporaryUploadedFile;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form of single file fields, one per upload-time outcome: a PNG-only `document` of at most
 * 1 KiB whose update hook and delete callback record in the request's {@see HookLog}, a
 * disabled one, one that is not deletable, a 2 KiB floor, a 2:1 ratio in each of its three
 * spellings, a choice of two ratios, a user's own image constraint, a type list with its own
 * message, one that fetches no file information and a private one. `mount()` fills the form
 * from the record it is given.
 */
#[AsLiveComponent(name: 'Forms:UploadForm', template: '@Fixtures/forms/UploadForm.html.twig')]
class UploadForm implements InteractsWithSchemas
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
                FileUpload::make('document')
                    ->acceptedFileTypes(['image/png'])
                    ->maxSize(1)
                    ->afterStateUpdated(function (): void {
                        $this->hookLog->record('document updated');
                    })
                    ->deleteUploadedFileUsing(function (string|TemporaryUploadedFile $file): void {
                        $this->hookLog->record('deleted: ' . (is_string($file) ? $file : 'upload ' . $file->getClientOriginalName()));
                    }),
                FileUpload::make('locked')->disabled(),
                FileUpload::make('kept')->deletable(false),
                FileUpload::make('floored')->minSize(2),
                FileUpload::make('ratio')->imageAspectRatio('2:1'),
                FileUpload::make('slashedRatio')->imageAspectRatio('2/1'),
                FileUpload::make('numericRatio')->imageAspectRatio('2'),
                FileUpload::make('ratios')->imageAspectRatio(['3:1', '4:1']),
                FileUpload::make('narrow')->constraint(new Image(maxWidth: 1)),
                FileUpload::make('typed')
                    ->acceptedFileTypes(['image/png'])
                    ->validationMessages([File::class => ['mimeTypesMessage' => 'Only PNG images.']]),
                FileUpload::make('fetchless')->fetchFileInformation(false),
                FileUpload::make('private')->visibility('private'),
            ]);
    }
}
