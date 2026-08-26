<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The edit page with every convention hook and every mutator declared, each
 * recording the point it ran at. The record of the run is a `LiveProp` because
 * the mount that fills the form happens on the request that builds the
 * component, and a plain property would not survive the dehydration a test
 * reads the page back through.
 */
final class EditHookedArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @var array<string>
     */
    #[LiveProp]
    public array $calls = [];

    /**
     * The form's raw title as `beforeSave` saw it — the pin for Filament's
     * order, where the hook fires inside the state read and therefore before
     * `mutateFormDataBeforeSave()` upper-cases anything.
     */
    public ?string $titleSeenBeforeSave = null;

    /**
     * The header modal whose success refills this page — the pin for the refill
     * running through `fillForm()` rather than a bare `fill()`.
     */
    public function editAction(): EditAction
    {
        return EditAction::make();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->editAction(),
        ];
    }

    protected function beforeFill(): void
    {
        $this->calls[] = 'beforeFill';
    }

    protected function afterFill(): void
    {
        $this->calls[] = 'afterFill';
    }

    protected function beforeValidate(): void
    {
        $this->calls[] = 'beforeValidate';
    }

    protected function afterValidate(): void
    {
        $this->calls[] = 'afterValidate';
    }

    protected function beforeSave(): void
    {
        $this->calls[] = 'beforeSave';

        $title = $this->getSchema('form')->getRawState()['title'] ?? null;
        $this->titleSeenBeforeSave = is_string($title) ? $title : null;
    }

    protected function afterSave(): void
    {
        $this->calls[] = 'afterSave';
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->calls[] = 'mutateFill';

        $title = $data['title'] ?? null;

        return [...$data, 'title' => is_string($title) ? mb_strtolower($title) : $title];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->calls[] = 'mutateSave';

        $title = $data['title'] ?? null;

        return [...$data, 'title' => is_string($title) ? mb_strtoupper($title) : $title];
    }
}
