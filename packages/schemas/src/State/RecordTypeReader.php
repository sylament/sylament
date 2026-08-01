<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Traversable;

final readonly class RecordTypeReader
{
    private PropertyTypeExtractorInterface $extractor;

    public function __construct(?PropertyTypeExtractorInterface $extractor = null)
    {
        $this->extractor = $extractor ?? new ReflectionExtractor();
    }

    /**
     * Describe what a record class declares at a dotted path, hopping object
     * segments by their declared class. Null when a segment's type cannot be
     * read or an intermediate is not a constructible object, so a caller keeps
     * whatever behaviour it had before types were consulted.
     *
     * @param class-string $recordClass
     */
    public function describe(string $recordClass, string $path): ?RecordTarget
    {
        $segments = explode('.', $path);
        $class = $recordClass;

        while (count($segments) > 1) {
            $target = $this->describeProperty($class, array_shift($segments));

            if ($target?->objectClass === null) {
                return null;
            }

            $class = $target->objectClass;
        }

        return $this->describeProperty($class, $segments[0]);
    }

    /**
     * @param class-string $class
     */
    public function describeProperty(string $class, string $property): ?RecordTarget
    {
        $type = $this->extractor->getType($class, $property);

        if (!$type instanceof Type) {
            return null;
        }

        $unwrapped = $type instanceof NullableType ? $type->getWrappedType() : $type;

        return new RecordTarget(
            class: $class,
            property: $property,
            objectClass: $this->resolveObjectClass($unwrapped),
            isNullable: $type->isNullable(),
            isCollection: $this->isCollection($unwrapped),
        );
    }

    /**
     * @return class-string|null
     */
    private function resolveObjectClass(Type $type): ?string
    {
        if (! $type instanceof ObjectType || $this->isCollection($type)) {
            return null;
        }

        $className = $type->getClassName();

        return class_exists($className) ? $className : null;
    }

    private function isCollection(Type $type): bool
    {
        if ($type instanceof CollectionType) {
            return true;
        }

        return $type instanceof ObjectType
            && is_a($type->getClassName(), Traversable::class, allow_string: true);
    }
}
