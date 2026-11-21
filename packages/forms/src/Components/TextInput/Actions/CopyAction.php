<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\TextInput\Actions;

use BackedEnum;
use Closure;
use Sylament\Actions\Action;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

class CopyAction extends Action
{
    protected string|TranslatableInterface|Closure|null $copyMessage = null;

    protected int|Closure|null $copyMessageDuration = null;

    private ?IconManager $iconManager = null;

    public static function getDefaultName(): ?string
    {
        return 'copy';
    }

    #[Required]
    public function setIconManager(IconManager $iconManager): void
    {
        $this->iconManager = $iconManager;
    }

    public function copyMessage(string|TranslatableInterface|Closure|null $message): static
    {
        $this->copyMessage = $message;

        return $this;
    }

    public function copyMessageDuration(int|Closure|null $duration): static
    {
        $this->copyMessageDuration = $duration;

        return $this;
    }

    public function getCopyMessage(mixed $state): string
    {
        return (string) $this->translateText($this->evaluate($this->copyMessage, ['state' => $state]) ?? new TranslatableMessage('components.text_input.actions.copy.message', domain: 'sylament-forms'));
    }

    public function getCopyMessageDuration(mixed $state): int
    {
        return $this->evaluate($this->copyMessageDuration, ['state' => $state]) ?? 2000;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(new TranslatableMessage('components.text_input.actions.copy.label', domain: 'sylament-forms'))
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_COPY, 'heroicons:clipboard-document-list-20-solid'))
            ->defaultColor('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->extraAttributes(static fn (CopyAction $action, mixed $state): array => [
                'data-action' => 'sylament--forms--text-input#copy',
                'class' => 'sy-fo-text-input-copy',
                'data-sylament--forms--text-input-message-param' => $action->getCopyMessage($state),
                'data-sylament--forms--text-input-duration-param' => $action->getCopyMessageDuration($state),
            ], merge: true);
    }

    /**
     * The icon a user's alias override names, or the shipped default.
     */
    private function resolveActionIcon(string $alias, string $fallback): string|BackedEnum|Markup
    {
        return ($this->iconManager ??= new IconManager())->resolve($alias) ?? $fallback;
    }
}
