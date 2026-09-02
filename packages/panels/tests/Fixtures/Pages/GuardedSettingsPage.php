<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Pages\Concerns\HasUnsavedDataChangesAlert;
use Sylament\Panels\Pages\Page;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\Width;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class GuardedSettingsPage extends Page
{
    use HasUnsavedDataChangesAlert;

    protected static ?string $slug = 'settings';

    protected static ?string $title = 'Probe Settings';

    protected ?string $template = '@PanelsFixtures/guarded_settings.html.twig';

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected ?Alignment $headerActionsAlignment = Alignment::End;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public int $touched = 0;

    /**
     * Overridden as a method: a class composing the trait cannot redeclare its
     * property with another default (a fatal at class load).
     */
    public function hasUnsavedDataChangesAlert(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->getSchema('form')->fill(['name' => 'Probe']);
        $this->rememberData();
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([TextInput::make('name')->required()]);
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->data = $this->getSchema('form')->getState();
        $this->rememberData();

        return null;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [
            Action::make('ping')->label('Ping')->action(fn (): null => null),
            ActionGroup::make([Action::make('grouped')->label('Grouped')->requiresConfirmation()->action(fn (): null => null)]),
        ];
    }

    /**
     * @return list<string>
     */
    public function getPageClasses(): array
    {
        return ['probe-settings-page'];
    }

    /**
     * @return array<string, scalar>
     */
    public function getExtraBodyAttributes(): array
    {
        return ['data-probe-body' => 'settings'];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save')->submit('save'),
            Action::make('touch')->label('Touch')->color('gray')->action(function (): void {
                $this->touched++;
            }),
        ];
    }
}
