<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Actions\Action;
use Sylament\Notifications\Notification;
use Sylament\Panels\Pages\SimplePage;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A simple page that keeps every default of the card: a title its heading repeats, a
 * subheading, and a body of one line beside an action that asks for confirmation before
 * it notifies.
 */
#[AsLiveComponent]
final class CardSimplePage extends SimplePage
{
    protected static ?string $title = 'Welcome back';

    protected ?string $subheading = 'Sign in to continue.';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make('Your account is ready.'),
            Actions::make([
                Action::make('resend')->requiresConfirmation()->action(static fn (): Notification => Notification::make()->title('Link sent')->send()),
            ]),
        ]);
    }
}
