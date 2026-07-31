<?php

declare(strict_types=1);

namespace Sylament\Support\Twig;

use BackedEnum;
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

    /**
     * @return array<string, string|true>
     */
    public function toScalarAttributes(): array
    {
        $scalars = [];

        foreach ($this->attributes as $name => $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            if (is_array($value) && in_array($name, self::APPENDABLE_ATTRIBUTES, true)) {
                $value = implode($name === 'style' ? '; ' : ' ', array_filter($value, is_string(...)));

                if ($value === '') {
                    continue;
                }
            }

            if (str_starts_with($name, 'aria-')) {
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                }
            }

            if (str_starts_with($name, 'data-')) {
                if ($value !== null && !is_scalar($value)) {
                    $value = json_encode($value, JSON_THROW_ON_ERROR);
                } elseif ($value === true) {
                    $value = 'true';
                }
            }

            if (is_iterable($value)) {
                $tokens = [];
                $hasEntries = false;

                foreach ($value as $token) {
                    if ($token === null) {
                        continue;
                    }

                    if ($token === false) {
                        continue;
                    }

                    $hasEntries = true;

                    if ($token !== true && (is_scalar($token) || $token instanceof Stringable)) {
                        $tokens[] = (string) $token;
                    }
                }

                if (!$hasEntries) {
                    continue;
                }

                $value = mb_trim(implode(' ', $tokens));
            }

            if ($value === null) {
                continue;
            }

            if ($value === false) {
                continue;
            }

            if ($value === true) {
                $scalars[$name] = true;

                continue;
            }

            if (!is_scalar($value) && !$value instanceof Stringable) {
                continue;
            }

            $scalars[$name] = (string) $value;
        }

        return $scalars;
    }

    public function toHtml(): string
    {
        $pairs = [];

        foreach ($this->toScalarAttributes() as $name => $value) {
            $pairs[] = sprintf(
                '%s="%s"',
                $this->escapeAttributeName($name),
                htmlspecialchars($value === true ? '' : $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return implode(' ', $pairs);
    }

    private function escapeAttributeName(string $name): string
    {
        $escaped = preg_replace_callback(
            '#[^a-zA-Z0-9,\.\-_:@\[\]]#Su',
            static function (array $matches): string {
                $char = $matches[0];
                $ord = ord($char[0]);

                if (($ord <= 0x1F && $char !== "\t" && $char !== "\n" && $char !== "\r") || ($ord >= 0x7F && $ord <= 0x9F)) {
                    return '&#xFFFD;';
                }

                if ($ord < 0x80) {
                    return match ($ord) {
                        34 => '&quot;',
                        38 => '&amp;',
                        60 => '&lt;',
                        62 => '&gt;',
                        default => sprintf('&#x%02X;', $ord),
                    };
                }

                return sprintf('&#x%04X;', mb_ord($char, 'UTF-8'));
            },
            $name,
        );

        if ($escaped !== null) {
            return $escaped;
        }

        return implode('', array_map(
            static fn (string $byte): string => sprintf('&#x%02X;', ord($byte[0])),
            mb_str_split($name, 1, '8bit'),
        ));
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
     * @return list<string>
     */
    public static function toTokenList(mixed $value): array
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
     * @return list<string>
     */
    public static function toDeclarationList(mixed $value): array
    {
        if (is_string($value)) {
            return self::splitStyleDeclarations($value)
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

    /**
     * @return list<string>
     */
    private static function splitStyleDeclarations(string $style): array
    {
        $declarations = [];
        $buffer = '';
        $depth = 0;
        $quote = null;

        foreach (mb_str_split($style) as $char) {
            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }
            } elseif ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth = max(0, $depth - 1);
            } elseif ($char === ';' && $depth === 0) {
                $declarations[] = $buffer;
                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        $declarations[] = $buffer;

        return $declarations;
    }
}
