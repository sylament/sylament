<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;

/**
 * A minimal rendering state leaf: binds to a state path and emits an `<input>`
 * carrying `getDataModel()`. The actions suite cannot use the real `forms`
 * fields (sibling package, not a dependency of `actions`), and the existing
 * `StateFieldFixture` deliberately has no template because nothing renders it —
 * so the modal-form binding tests need their own. Twin of schemas'
 * `LiveField` fixture. It declares, as a form field does, that it shows its
 * own errors, though it carries no constraints.
 */
class ActionSchemaField extends Component implements HasValidationConstraints
{
    protected string|Closure|null $template = '@ActionsFixtures/components/ActionSchemaField.html.twig';

    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }

    public function getValidationConstraints(): array
    {
        return [];
    }

    public function dehydrateValidationConstraints(array &$constraints): void
    {
    }
}
