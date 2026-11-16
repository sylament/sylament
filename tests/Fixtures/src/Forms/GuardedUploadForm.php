<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\FileUpload;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Forms\Record\GuardedDocument;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form bound to a record holding `owned.png` as its `document`, in the shape `$recordShape`
 * names: an array, an object, an object whose property is uninitialised, or no record. Its
 * `document` field refuses a stored path the record does not hold, `shared` also admits one
 * under `public/`, and `open` has no gate. `mount()` fills the form with its defaults; `save()`
 * validates it.
 */
#[AsLiveComponent(name: 'Forms:GuardedUploadForm', template: '@Fixtures/forms/GuardedUploadForm.html.twig')]
class GuardedUploadForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public const string OWNED = 'owned.png';

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public string $recordShape = 'array';

    public function mount(): void
    {
        $this->getSchema('form')->fill();
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record(match ($this->recordShape) {
                'array' => ['document' => self::OWNED],
                'object' => new GuardedDocument(self::OWNED),
                'uninitialised' => new GuardedDocument(),
                default => null,
            })
            ->components([
                FileUpload::make('document')->preventFilePathTampering(),
                FileUpload::make('shared')->preventFilePathTampering(allowFilePathUsing: static fn (string $file): bool => str_starts_with($file, 'public/')),
                FileUpload::make('open'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->getSchema('form')->getState();
    }
}
