<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\TextInput\Actions;

use BackedEnum;
use Sylament\Actions\Action;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Support\Icons\IconManager;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

class ShowPasswordAction extends Action
{
    private ?IconManager $iconManager = null;

    public static function getDefaultName(): ?string
    {
        return 'showPassword';
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
            ->label('Show password')
            ->icon($this->resolveActionIcon(FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD, 'heroicons:eye-20-solid'))
            ->defaultColor('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->extraAttributes([
                'data-action' => 'sylament--forms--text-input#reveal',
                'class' => 'sy-fo-text-input-show-password',
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
