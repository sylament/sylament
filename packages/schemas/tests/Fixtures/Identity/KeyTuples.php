<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Identity;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Stringable;
use Sylament\Schemas\Identity\IdentifierFields;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Seeded inputs for the codec's property test: identifier values of every type
 * the fixtures map, the ways a key gets misspelt, and arbitrary client input.
 */
final readonly class KeyTuples
{
    private const array SPECIAL = ['~', '=', '/', '.', '%', '+', ' ', '-', '_', '#', '?', '&', ':', ';', ',', '|', '(', ')', '[', ']', '<', '>', '"', "'", '`', '\\', '@', '!', '*', '$', '^', '{', '}', "\t", "\n", "\r", "\x0B", "\x7F", "\x01", "\x1F"];

    private const array MULTIBYTE = ['Ž', 'á', 'é', "e\u{0301}", 'ß', 'İ', 'ı', 'ǅ', 'ﬀ', 'ΐ', '日本', '😀', "\u{00A0}", "\u{2003}", "\u{200B}", "\u{FEFF}", "\u{202E}", 'Ω', 'Ω', 'Å', 'Å', "\u{FFFD}", "\u{10FFFF}", 'ǰ', 'ŉ'];

    private Randomizer $random;

    public function __construct(int $seed)
    {
        $this->random = new Randomizer(new Mt19937($seed));
    }

    /**
     * One identifier tuple for the class: the values a new record would hold, and
     * the values a read of its row would hand back.
     *
     * @param class-string $class
     *
     * @return array{list<mixed>, list<mixed>}
     */
    public function identifier(EntityManagerInterface $entityManager, string $class): array
    {
        $raw = [];
        $php = [];

        foreach (array_keys(IdentifierFields::of($entityManager, $class)->names) as $index) {
            [$raw[], $php[]] = $this->field($entityManager, $class, $index);
        }

        return [$raw, $php];
    }

    /**
     * A new, unmanaged record of the class carrying the raw identifier values; an
     * association half gets a target built the same way.
     *
     * @param class-string $class
     * @param list<mixed>  $values
     */
    public function record(EntityManagerInterface $entityManager, string $class, array $values): object
    {
        $metadata = $entityManager->getClassMetadata($class);
        $record = $metadata->newInstance();

        foreach ($metadata->identifier as $index => $field) {
            $value = $values[$index];

            if ($metadata->hasAssociation($field)) {
                $value = $this->record($entityManager, $metadata->getAssociationTargetClass($field), [$value]);
            }

            $metadata->setFieldValue($record, $field, $value);
        }

        return $record;
    }

    /**
     * Every mutator applied once to the key, the misspellings a client could send.
     *
     * @return array<string, string> mutator name ⇒ mutant
     */
    public function mutants(string $key): array
    {
        $mutators = [
            'lower-escape' => static fn (string $k): ?string => preg_replace_callback('/=([0-9A-F]{2})/', static fn (array $m): string => '=' . mb_strtolower($m[1]), $k, 1),
            'add-sep' => fn (string $k): string => substr_replace($k, '~', $this->random->getInt(0, mb_strlen($k)), 0),
            'drop-sep' => static fn (string $k): ?string => str_contains($k, '~') ? preg_replace('/~/', '', $k, 1) : $k . '~',
            'double-sep' => static fn (string $k): ?string => str_contains($k, '~') ? preg_replace('/~/', '~~', $k, 1) : '~' . $k,
            'leading-zero' => fn (string $k): string => implode('~', array_map(fn (string $c): string => $this->chance(0.5) ? '0' . $c : $c, explode('~', $k))),
            'plus' => fn (string $k): string => implode('~', array_map(fn (string $c): string => $this->chance(0.5) ? '+' . $c : $c, explode('~', $k))),
            'plus-escaped' => fn (string $k): string => implode('~', array_map(fn (string $c): string => $this->chance(0.5) ? '=2B' . $c : $c, explode('~', $k))),
            'minus' => fn (string $k): string => implode('~', array_map(fn (string $c): string => $this->chance(0.5) ? '-' . $c : $c, explode('~', $k))),
            'ws' => fn (string $k): string => $this->pick([' ', "\n", "\t", "\r", "\0", "\x0B", "\u{00A0}"]) . $k . ($this->chance(0.5) ? $this->pick([' ', "\n"]) : ''),
            'ws-trailing' => fn (string $k): string => $k . $this->pick([' ', "\n", "\r\n", "\t", "\0"]),
            'ws-escaped' => fn (string $k): string => implode('~', array_map(fn (string $c): string => $this->chance(0.5) ? $c . $this->pick(['=20', '=0A', '=09', '=C2=A0']) : $this->pick(['=20', '=0A']) . $c, explode('~', $k))),
            'needless-escape' => function (string $k): string {
                $ok = [];

                for ($i = 0; $i < mb_strlen($k); $i++) {
                    if (preg_match('/[A-Za-z0-9_-]/', $k[$i]) && ($i < 1 || $k[$i - 1] !== '=') && ($i < 2 || $k[$i - 2] !== '=')) {
                        $ok[] = $i;
                    }
                }

                if ($ok === []) {
                    return $k;
                }

                $i = $this->pick($ok);

                return substr_replace($k, sprintf('=%02X', ord($k[$i])), $i, 1);
            },
            'overlong' => fn (string $k): string => preg_replace('/=2F|=2E|=7E/', $this->pick(['=C0=AF', '=E0=80=AF', '=C0=AE', '=C1=BE']), $k, 1) ?: $k . '=C0=AF',
            'invalid-utf8' => fn (string $k): string => substr_replace($k, $this->pick(['=FF', '=C3', '=ED=A0=80', '=F4=90=80=80', '=80', '=C3=28', '=00']), $this->random->getInt(0, mb_strlen($k)), 0),
            'short-escape' => fn (string $k): string => substr_replace($k, $this->pick(['=2', '=', '==', '=G1', '=2g']), $this->random->getInt(0, mb_strlen($k)), 0),
            'unescape-one' => static fn (string $k): ?string => preg_replace_callback('/=([0-9A-F]{2})/', static fn (array $m): string => chr((int) hexdec($m[1])), $k, 1),
            'raw-byte' => fn (string $k): string => substr_replace($k, chr($this->random->getInt(0, 255)), $this->random->getInt(0, mb_strlen($k)), 0),
            'byte-flip' => function (string $k): string {
                if ($k === '') {
                    return $k;
                }

                $i = $this->random->getInt(0, mb_strlen($k) - 1);

                return substr_replace($k, chr(ord($k[$i]) ^ (1 << $this->random->getInt(0, 7))), $i, 1);
            },
            'case-flip' => function (string $k): string {
                $idx = [];

                for ($i = 0; $i < mb_strlen($k); $i++) {
                    if (ctype_alpha($k[$i]) && ($i < 1 || $k[$i - 1] !== '=') && ($i < 2 || $k[$i - 2] !== '=')) {
                        $idx[] = $i;
                    }
                }

                if ($idx === []) {
                    return $k;
                }

                $i = $this->pick($idx);

                return substr_replace($k, ctype_upper($k[$i]) ? mb_strtolower($k[$i]) : mb_strtoupper($k[$i]), $i, 1);
            },
            'swap-components' => static fn (string $k): string => implode('~', array_reverse(explode('~', $k))),
            'dup-component' => static fn (string $k): string => $k . '~' . explode('~', $k)[0],
            'percent' => rawurlencode(...),
            'percent-sep' => static fn (string $k): string => str_replace('~', '%7E', $k),
            'nul-escape' => fn (string $k): string => implode('~', array_map(fn (string $c): string => $this->chance(0.5) ? $c . '=00zzz' : $c, explode('~', $k))),
            'numeric-forms' => fn (string $k): string => implode('~', array_map(fn (string $c): string => ctype_digit(mb_ltrim($c, '-')) && $c !== '' ? $this->pick([$c . '.0', $c . 'e0', '0x' . dechex((int) $c), $c . '.', '.' . $c, str_replace('-', '=2D', $c), "\u{FF10}" . $c, "\u{0663}", $c . '_']) : $c, explode('~', $k))),
            'truncate' => fn (string $k): string => mb_substr($k, 0, $this->random->getInt(0, max(0, mb_strlen($k) - 1))),
            'uuid-forms' => fn (string $k): ?string => preg_replace_callback('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', fn (array $m): string => $this->pick([mb_strtoupper($m[0]), str_replace('-', '', $m[0]), '{' . $m[0] . '}', 'urn:uuid:' . $m[0], Uuid::fromString($m[0])->toBase58(), Uuid::fromString($m[0])->toBase32()]), $k, 1),
            'bool-forms' => fn (string $k): ?string => preg_replace('/^(0|1)$/', $this->pick(['true', 'false', '00', '01', '2', '-1', 'on', ' 1', '1 ']), $k),
            'decimal-forms' => fn (string $k): string => preg_match('/^-?\d+(\.\d+)?$/', $k) ? $this->pick([$k . '0', str_contains($k, '.') ? $k . '00' : $k . '.0', '0' . $k, '+' . $k, $k . 'e0', '-0', '-0.0', '.5', '1.', ' ' . $k]) : $k,
            'sku-case' => static fn (string $k): ?string => preg_replace_callback('/[A-Z]{2}=2D|[A-Z]{2}-/', static fn (array $m): string => mb_strtolower($m[0]), $k, 1),
            'nfd' => static fn (string $k): string => str_replace(['=C3=A9', '=C3=81'], ['e=CC=81', 'A=CC=81'], $k),
        ];

        $mutants = [];

        foreach ($mutators as $name => $mutator) {
            $mutant = $mutator($key);

            if (is_string($mutant) && $mutant !== $key) {
                $mutants[$name] = $mutant;
            }
        }

        return $mutants;
    }

    /**
     * Arbitrary client input: raw bytes, grammar-shaped strings, numeric edge cases
     * and near-UUIDs, as totality.php's four generators draw them.
     */
    public function garbage(): string
    {
        $generators = [
            'bytes' => function (): string {
                $s = '';
                $l = $this->random->getInt(0, 40);

                for ($i = 0; $i < $l; $i++) {
                    $s .= chr($this->random->getInt(0, 255));
                }

                return $s;
            },
            'grammar' => function (): string {
                $parts = [];
                $n = $this->random->getInt(1, 4);

                for ($j = 0; $j < $n; $j++) {
                    $c = '';
                    $l = $this->random->getInt(0, 6);

                    for ($i = 0; $i < $l; $i++) {
                        $c .= $this->chance(0.5) ? $this->pick(mb_str_split('abcXYZ019_-')) : '=' . sprintf('%02X', $this->random->getInt(0, 255));
                    }

                    $parts[] = $c;
                }

                return implode('~', $parts);
            },
            'numeric' => fn (): string => $this->pick(['', '-', '+', '0', '00', '-0', '0.0', '1e9', '9223372036854775807', '9223372036854775808', '-9223372036854775809', ' 1', '1 ', '0x10', '1_000', '١']) . $this->pick(['', '~', '~1', '~a', '=7E1']),
            'uuidish' => function (): string {
                $u = (string) $this->uuidOfVersion(4);

                return $this->pick([$u, mb_strtoupper($u), $u . '~' . $this->random->getInt(-5, 5), str_replace('-', '', $u), mb_substr($u, 0, 35), $u . 'x']);
            },
        ];

        return $generators[$this->pick(array_keys($generators))]();
    }

    public static function show(mixed $value): string
    {
        if (is_array($value)) {
            $shown = [];

            foreach ($value as $index => $item) {
                $shown[] = (array_is_list($value) ? '' : $index . ': ') . self::show($item);
            }

            return '[' . implode(', ', $shown) . ']';
        }

        if ($value instanceof BackedEnum) {
            return $value::class . '::' . $value->name;
        }

        if ($value instanceof Stringable) {
            return $value::class . '(' . self::hexdump((string) $value) . ')';
        }

        if (is_string($value)) {
            return self::hexdump($value);
        }

        return is_object($value) ? get_debug_type($value) : var_export($value, true);
    }

    private static function hexdump(string $s): string
    {
        $out = '';

        foreach (mb_str_split($s, 1, '8bit') as $c) {
            $o = ord($c[0]);
            $out .= ($o >= 0x21 && $o <= 0x7E) ? $c : sprintf('\\x%02X', $o);
        }

        return '"' . $out . '"';
    }

    /**
     * @param class-string $class
     *
     * @return array{mixed, mixed} the raw value a record property can hold, and its PHP form
     */
    private function field(EntityManagerInterface $entityManager, string $class, int $index): array
    {
        $fields = IdentifierFields::of($entityManager, $class);
        $type = $fields->types[$index];
        $name = $fields->names[$index];
        $metadata = $entityManager->getClassMetadata($class);
        $platform = $entityManager->getConnection()->getDatabasePlatform();

        $owner = $metadata;
        $field = $name;

        while ($owner->hasAssociation($field)) {
            $owner = $entityManager->getClassMetadata($owner->getAssociationTargetClass($field));
            $field = $owner->getSingleIdentifierFieldName();
        }

        $enum = $owner->fieldMappings[$field]->enumType ?? null;

        $raw = match (true) {
            $enum !== null => $this->pick($enum::cases()),
            $type === 'integer', $type === 'smallint' => $this->int(),
            $type === 'bigint' => $this->chance(0.05) ? $this->pick(['9223372036854775808', '-9223372036854775809', '99999999999999999999999']) : $this->int(),
            $type === 'guid' => $this->chance(0.8) ? $this->uuidString() : $this->string(),
            $type === 'binary_uuid' => $this->uuid(),
            $type === 'uuid' => Uuid::fromBinary($this->random->getBytes(16)),
            $type === 'ulid' => Ulid::fromBinary($this->random->getBytes(16)),
            $type === 'sku' => new Sku(chr($this->random->getInt(97, 122)) . chr($this->random->getInt(65, 90)) . '-' . $this->random->getInt(0, PHP_INT_MAX)),
            $type === 'cipher' => new Cipher($this->string()),
            $type === 'boolean' => $this->chance(0.5),
            $type === 'decimal' => ($this->chance(0.5) ? '-' : '') . $this->random->getInt(0, 1000000) . ($this->chance(0.5) ? '.' . $this->random->getInt(0, 99) : ''),
            default => $this->string(),
        };

        if ($enum !== null) {
            return [$raw, $raw];
        }

        $doctrineType = Type::getType($type);

        return [$raw, $doctrineType->convertToPHPValue($doctrineType->convertToDatabaseValue($raw, $platform), $platform)];
    }

    /**
     * A printable string: valid UTF-8, no NUL, not blank.
     */
    private function string(int $maxLength = 40): string
    {
        do {
            $mode = $this->random->getInt(0, 9);
            $length = $this->chance(0.02) ? $this->random->getInt(200, 3000) : $this->random->getInt(1, $maxLength);
            $s = '';

            for ($i = 0; $i < $length; $i++) {
                $s .= match (true) {
                    $mode <= 2 => chr($this->random->getInt(0x21, 0x7E)),
                    $mode <= 4 => $this->pick(self::SPECIAL),
                    $mode <= 6 => $this->pick(self::MULTIBYTE),
                    $mode === 7 => mb_chr($this->random->getInt(0x80, 0xD7FF), 'UTF-8'),
                    $mode === 8 => $this->pick(['a', 'b', 'Z', '0', '9', '-', '_']),
                    default => $this->chance(0.5) ? chr($this->random->getInt(1, 0x7F)) : (mb_chr($this->random->getInt(0x80, 0x10FFFF), 'UTF-8') ?: 'x'),
                };
            }

            if ($this->chance(0.05)) {
                $s = ' ' . $s . ' ';
            }

            if ($this->chance(0.02)) {
                $s = $this->pick(['0', '00', '-0', '1e3', '0x1A', '+5', 'NaN', 'INF', 'null', 'true', 'false', ' 5', '5 ', "5\n", '٣']);
            }
        } while (IdentifierFields::printable($s) === null);

        return $s;
    }

    private function int(): int
    {
        return match ($this->random->getInt(0, 7)) {
            0 => 0,
            1 => PHP_INT_MAX,
            2 => PHP_INT_MIN,
            3 => $this->random->getInt(-10, 10),
            4 => $this->random->getInt(PHP_INT_MIN, PHP_INT_MAX),
            5 => -$this->random->getInt(1, PHP_INT_MAX),
            6 => $this->pick([PHP_INT_MAX - 1, PHP_INT_MIN + 1, 2147483647, -2147483648, 2147483648, 32767, -32768]),
            default => $this->random->getInt(0, 1_000_000),
        };
    }

    private function uuidString(): string
    {
        return match ($this->random->getInt(0, 3)) {
            0 => (string) $this->uuidOfVersion(4),
            1 => (string) $this->uuidOfVersion(7),
            2 => mb_strtoupper((string) $this->uuidOfVersion(4)),
            default => (string) $this->uuidOfVersion(1),
        };
    }

    private function uuid(): Uuid
    {
        return match ($this->random->getInt(0, 3)) {
            0 => $this->uuidOfVersion(4),
            1 => $this->uuidOfVersion(7),
            2 => $this->uuidOfVersion(1),
            default => Uuid::fromBinary($this->random->getBytes(16)),
        };
    }

    /**
     * Seeded bytes stamped with the version and the RFC 9562 variant, so the
     * UUID parses as that version's class.
     */
    private function uuidOfVersion(int $version): Uuid
    {
        $bytes = $this->random->getBytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | ($version << 4));
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return Uuid::fromBinary($bytes);
    }

    private function chance(float $probability): bool
    {
        return $this->random->nextFloat() < $probability;
    }

    /**
     * @template T
     *
     * @param list<T> $list
     *
     * @return T
     */
    private function pick(array $list): mixed
    {
        return $list[$this->random->getInt(0, count($list) - 1)];
    }
}
