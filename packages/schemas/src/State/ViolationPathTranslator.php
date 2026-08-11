<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasKeyedItems;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\PropertyPath;

use function Sylament\Support\filled;

/**
 * Speak a record-side violation path back in state terms: the property path a
 * `ConstraintViolation` carries indexes the mapped object graph, while an error
 * bag is keyed by the absolute state paths the schema's components declare. The
 * record path map is the inverse of the mapping walk, so translating is walking
 * it — deepest declared path first, then whatever of the remainder the owning
 * component can still name.
 */
final readonly class ViolationPathTranslator
{
    /**
     * @param array<string, Component> $recordPathMap record-relative paths keyed
     *                                                to the components that map them
     * @param string|null              $rootStatePath the schema's own state path,
     *                                                the prefix a record path is
     *                                                relative to
     */
    public function __construct(private array $recordPathMap, private ?string $rootStatePath)
    {
    }

    /**
     * The absolute state path owning this violation, or null when no declared
     * path does — the caller folds those at the schema's own path rather than
     * dropping them.
     */
    public function translate(string $violationPropertyPath): ?string
    {
        try {
            $propertyPath = new PropertyPath($violationPropertyPath);
        } catch (InvalidPropertyPathException) {
            // A class-level constraint carries the empty path, and a violation
            // built by hand may carry one Symfony's own parser rejects: neither
            // indexes the record graph, so no declared path can own it.
            return null;
        }

        $elements = $propertyPath->getElements();
        $matchedDepth = $this->matchDepth($propertyPath);

        if ($matchedDepth === null) {
            return null;
        }

        $recordPath = implode('.', array_slice($elements, 0, $matchedDepth));
        $statePath = filled($this->rootStatePath) ? $this->rootStatePath . '.' . $recordPath : $recordPath;

        $component = $this->recordPathMap[$recordPath];

        if (! $component instanceof HasKeyedItems) {
            return $statePath;
        }

        $itemKey = $this->resolveItemKey($component, $propertyPath, $matchedDepth);

        if ($itemKey === null) {
            return $statePath;
        }

        $statePath .= '.' . $itemKey;

        for ($index = $matchedDepth + 1; $index < $propertyPath->getLength(); $index++) {
            if ($propertyPath->isIndex($index)) {
                break;
            }

            $statePath .= '.' . $elements[$index];
        }

        return $statePath;
    }

    /**
     * How many leading property elements of the violation path the deepest
     * declared record path covers, or null when none does. Deepest wins, so a
     * declared `author.name` beats a declared `author` — the map never carries
     * both unless the shallower one is a leaf in its own right.
     */
    private function matchDepth(PropertyPath $propertyPath): ?int
    {
        $matchedDepth = null;
        $recordPath = '';

        foreach ($propertyPath->getElements() as $index => $element) {
            if ($propertyPath->isIndex($index)) {
                break;
            }

            $recordPath = ($index === 0) ? $element : $recordPath . '.' . $element;

            if (array_key_exists($recordPath, $this->recordPathMap)) {
                $matchedDepth = $index + 1;
            }
        }

        return $matchedDepth;
    }

    /**
     * The state key of the item the violation's index names, or null when the
     * element is not an index, names no position the component currently holds,
     * or is a collection key rather than a position.
     */
    private function resolveItemKey(HasKeyedItems $component, PropertyPath $propertyPath, int $matchedDepth): ?string
    {
        if ($matchedDepth >= $propertyPath->getLength()) {
            return null;
        }

        if (! $propertyPath->isIndex($matchedDepth)) {
            return null;
        }

        $position = $propertyPath->getElement($matchedDepth);

        if (! ctype_digit($position)) {
            return null;
        }

        return $component->getItemKeys()[(int) $position] ?? null;
    }
}
