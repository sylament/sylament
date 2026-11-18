<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use BackedEnum;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Notifications\Notification;
use Sylament\Panels\Pages\Page;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\Width;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A hand-written page that sets every page-level knob: its title, heading and subheading,
 * its navigation entry, breadcrumbs, root class, body attribute, content width, a body, a
 * sub-navigation band, and a header of actions aligned to the end. The header declares an
 * action through its method, one inline that notifies, one that asks for confirmation, a
 * link to the notes page in the panel the page was opened in (its URL minted as the list is
 * declared), a `help` action with no URL of its own that the page links by default, a
 * `leave` action that redirects to the notes page once it has run, and two groups, the one
 * leaving its placement to the page holding an action that notifies. Building its body
 * records `content` into the {@see HookLog}, and resolving the redirect records `leave`.
 *
 * A live request on it carries the `panelId` of the panel it was opened in, as a GET of its
 * route mints it; built outside any panel, it would answer for the default one, which does
 * not list it.
 */
#[AsLiveComponent]
final class SettingsPage extends Page
{
    protected static ?string $title = 'Site settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicons:home';

    protected static string|BackedEnum|null $activeNavigationIcon = 'heroicons:rectangle-stack';

    protected static ?string $navigationGroup = 'Configuration';

    protected ?string $heading = 'Configure the site';

    protected ?string $subheading = 'Changes apply to every visitor.';

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected ?Alignment $headerActionsAlignment = Alignment::End;

    private UrlGeneratorInterface $urlGenerator;

    private HookLog $hookLog;

    #[Required]
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public static function getNavigationBadge(): string
    {
        return '3';
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public function getHeaderActions(): array
    {
        return [
            $this->archiveAction(),
            Action::make('ping')->action(static fn (): Notification => Notification::make()->title('Pong')->send()),
            Action::make('reset')->requiresConfirmation()->action(static fn (): Notification => Notification::make()->title('Reset')->send()),
            Action::make('notes')->url($this->urlGenerator->generate(NotesPage::getRouteName($this->getPanel()))),
            Action::make('help'),
            Action::make('leave')->successRedirectUrl(function (): string {
                $this->hookLog->record('leave');

                return '/pages/notes-page';
            }),
            ActionGroup::make([Action::make('export')->action(static fn (): Notification => Notification::make()->title('Exported')->send()), Action::make('import')]),
            ActionGroup::make([Action::make('share')])->dropdownPlacement('top-start'),
        ];
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        return $action->getName() === 'help' ? 'https://example.com/help' : parent::getDefaultActionUrl($action);
    }

    public function archiveAction(): Action
    {
        return Action::make('archive')->action(static fn (): Notification => Notification::make()->title('Archived')->send());
    }

    public function getBreadcrumbs(): array
    {
        return ['/pages/notes-page' => 'Pages', 'Settings'];
    }

    public function getPageClasses(): array
    {
        return ['sy-pa-settings-page'];
    }

    public function getExtraBodyAttributes(): array
    {
        return ['data-page' => 'settings'];
    }

    public function content(Schema $schema): Schema
    {
        $this->hookLog->record('content');

        return $schema->components([Text::make('Every setting applies to the whole site.')]);
    }

    public function getSubNavigation(): array
    {
        return $this->generateNavigationItems([NotesPage::class, StandalonePage::class]);
    }
}
