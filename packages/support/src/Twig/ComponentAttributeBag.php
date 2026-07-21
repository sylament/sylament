<?php

declare(strict_types=1);

namespace Sylament\Support\Twig;

use Stringable;

class ComponentAttributeBag
{
    private const array APPENDABLE_ATTRIBUTES = ['class', 'style'];

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * @param array<mixed> $attributes
     */
    final public function __construct(array $attributes = [])
    {
        foreach ($attributes as $name => $value) {
            $this->attributes[(string) $name] = $this->normalize((string) $name, $value);
        }
    }

    /**
     * @param array<mixed> $attributeDefaults
     */
    public function merge(array $attributeDefaults): static
    {
        $merged = new static($attributeDefaults);

        foreach ($this->attributes as $name => $value) {
            $default = $merged->attributes[$name] ?? null;

            $merged->attributes[$name] = in_array($name, self::APPENDABLE_ATTRIBUTES, true) && is_array($default) && is_array($value)
                ? [...$default, ...$value]
                : $value;
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    private function normalize(string $name, mixed $value): mixed
    {
        return match ($name) {
            'class' => self::toTokenList($value),
            'style' => self::toDeclarationList($value),
            default => $value,
        };
    }

    /**
     * Normalize a class value into a flat token list: strings split on whitespace,
     * html_classes-style conditional maps resolve to their truthy string keys, and
     * nested lists flatten. Boolean and null entries are dropped, matching what
     * html_attr()'s token list would omit at render.
     *
     * @return list<string>
     */
    private static function toTokenList(mixed $value): array
    {
        if (is_string($value)) {
            return array_values(array_filter(mb_split('\s+', $value) ?: [], static fn (string $token): bool => $token !== ''));
        }

        if (!is_iterable($value)) {
            return $value instanceof Stringable ? self::toTokenList((string) $value) : [];
        }

        $tokens = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                if ((bool) $item) {
                    $tokens[] = $key;
                }

                continue;
            }

            $tokens = [...$tokens, ...self::toTokenList($item)];
        }

        return $tokens;
    }

    /**
     * Normalize a style value into a list of declarations: strings split on ";",
     * maps resolve to "property: value" pairs, and nested lists flatten.
     *
     * @return list<string>
     */
    private static function toDeclarationList(mixed $value): array
    {
        if (is_string($value)) {
            return explode(';', $value)
                    |> (static fn ($x): array => array_map(mb_trim(...), $x))
                    |> (static fn ($x): array => array_filter($x, static fn (string $declaration): bool => $declaration !== ''))
                    |> array_values(...);
        }

        if (!is_iterable($value)) {
            return $value instanceof Stringable ? self::toDeclarationList((string) $value) : [];
        }

        $declarations = [];
        foreach ($value as $property => $item) {
            if (is_string($property)) {
                if (is_scalar($item) || $item instanceof Stringable) {
                    $declarations[] = "{$property}: {$item}";
                }

                continue;
            }

            $declarations = [...$declarations, ...self::toDeclarationList($item)];
        }

        return $declarations;
    }
}
