<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Forms\Components\FusedGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasHelperText
{
    protected ?Closure $helperTextSchema = null;

    public function helperText(string|Markup|TranslatableInterface|Closure|null $text): static
    {
        $this->helperTextSchema = static function (Component $component) use ($text): ?Schema {
            $content = $component->translateText($component->evaluate($text));

            if (blank($content)) {
                return null;
            }

            $schema = $component->makeChildSchema(static::BELOW_CONTENT_SCHEMA_KEY)
                ->components([Text::make($content)]);

            if (filled($id = $component->getId())) {
                $schema->extraAttributes(['id' => "{$id}-helper-text"], merge: true);
            }

            return $schema;
        };

        $this->belowContent($this->helperTextSchema);

        return $this;
    }

    public function getHelperTextId(): ?string
    {
        if (
            (!$this->helperTextSchema instanceof Closure)
            || (($this->childComponents[static::BELOW_CONTENT_SCHEMA_KEY] ?? null) !== $this->helperTextSchema)
            || blank($this->getId())
            || ($this->getContainer()->getFieldWrapperView() === FusedGroup::PLAIN_FIELD_WRAPPER_VIEW)
        ) {
            return null;
        }

        $schema = $this->getChildSchema(static::BELOW_CONTENT_SCHEMA_KEY);

        if ((!$schema instanceof Schema) || ($schema->getComponents(withHidden: false) === [])) {
            return null;
        }

        $id = $schema->getExtraAttributes()['id'] ?? null;

        return filled($id) && is_string($id) ? $id : null;
    }
}
