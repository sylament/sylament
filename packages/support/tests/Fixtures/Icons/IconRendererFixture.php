<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Icons;

use Symfony\UX\Icons\IconRendererInterface;

class IconRendererFixture implements IconRendererInterface
{
    public ?string $lastName = null;

    /**
     * @var array<string, string|bool>
     */
    public array $lastAttributes = [];

    public function renderIcon(string $name, array $attributes = []): string
    {
        $this->lastName = $name;
        $this->lastAttributes = $attributes;

        return sprintf('<svg data-icon="%s"></svg>', $name);
    }
}
