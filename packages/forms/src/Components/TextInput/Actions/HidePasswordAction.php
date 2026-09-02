<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\TextInput\Actions;

use BackedEnum;
use Sylament\Actions\Action;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Support\Icons\IconManager;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

class HidePasswordAction extends Action
{
    private ?IconManager $iconManager = null;

    public static function getDefaultName(): ?string
    {
        return 'hidePassword';
    }

    #[Required]
    public function setIconManager(IconManager $iconManager): void
    {
        $this->iconManager = $iconManager;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Hide password')
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD, 'heroicons:eye-slash-20-solid'))
            ->defaultColor('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->extraAttributes([
                'data-action' => 'sylament--forms--text-input#conceal',
                'class' => 'sy-fo-text-input-hide-password',
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
