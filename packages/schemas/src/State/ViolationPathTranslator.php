<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasKeyedItems;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
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
        $match = $this->matchRecordPath($violationPropertyPath);

        if ($match === null) {
            return null;
        }

        [$propertyPath, $matchedDepth, $recordPath] = $match;

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

        $remainder = $this->readItemRemainder($propertyPath, $matchedDepth);

        if ($remainder === []) {
            return $statePath;
        }

        $declaredDepth = $this->matchItemDepth($remainder, $component->getItemStatePaths());

        if ($declaredDepth === null) {
            return $statePath;
        }

        return $statePath . '.' . implode('.', array_slice($remainder, 0, $declaredDepth));
    }

    /**
     * The absolute state path a violation's message is shown under:
     * translate()'s answer when the component owning it shows its own errors —
     * a field declaring validation constraints, or a component keying items —
     * and null when it shows none, as a layout component entangling a related
     * record does, so the caller folds the message at the schema's own path.
     */
    public function translateToErrorPath(string $violationPropertyPath): ?string
    {
        $recordPath = $this->matchRecordPath($violationPropertyPath)[2] ?? null;

        if ($recordPath === null) {
            return null;
        }

        $component = $this->recordPathMap[$recordPath];

        if (! $component instanceof HasValidationConstraints && ! $component instanceof HasKeyedItems) {
            return null;
        }

        return $this->translate($violationPropertyPath);
    }

    /**
     * The parsed violation path, how many of its leading elements the deepest
     * declared record path covers, and that record path — or null when the
     * path does not parse or no declared path covers it.
     *
     * @return array{PropertyPath, int, string}|null
     */
    private function matchRecordPath(string $violationPropertyPath): ?array
    {
        try {
            $propertyPath = new PropertyPath($violationPropertyPath);
        } catch (InvalidPropertyPathException) {
            // A class-level constraint carries the empty path, and a violation
            // built by hand may carry one Symfony's own parser rejects: neither
            // indexes the record graph, so no declared path can own it.
            return null;
        }

        $matchedDepth = $this->matchDepth($propertyPath);

        if ($matchedDepth === null) {
            return null;
        }

        return [$propertyPath, $matchedDepth, implode('.', array_slice($propertyPath->getElements(), 0, $matchedDepth))];
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
     * element is not an index or names a collection key the component holds no
     * item under.
     */
    private function resolveItemKey(HasKeyedItems $component, PropertyPath $propertyPath, int $matchedDepth): ?string
    {
        if ($matchedDepth >= $propertyPath->getLength()) {
            return null;
        }

        if (! $propertyPath->isIndex($matchedDepth)) {
            return null;
        }

        return $component->getItemKeys()[$propertyPath->getElement($matchedDepth)] ?? null;
    }

    /**
     * The property elements the violation carries inside the item, stopping at
     * any further index — an item's own collection is a second keying this
     * translator cannot resolve.
     *
     * @return list<string>
     */
    private function readItemRemainder(PropertyPath $propertyPath, int $matchedDepth): array
    {
        $elements = $propertyPath->getElements();
        $remainder = [];

        for ($index = $matchedDepth + 1; $index < $propertyPath->getLength(); $index++) {
            if ($propertyPath->isIndex($index)) {
                break;
            }

            $remainder[] = $elements[$index];
        }

        return $remainder;
    }

    /**
     * How many leading elements of the in-item remainder the deepest path the
     * item schema declares covers, or null when none does — the item's own
     * repetition of the map walk, so a violation inside an item folds by the
     * same rule as one at the root.
     *
     * @param list<string> $remainder
     * @param list<string> $itemStatePaths
     */
    private function matchItemDepth(array $remainder, array $itemStatePaths): ?int
    {
        $declared = array_flip($itemStatePaths);
        $matchedDepth = null;
        $itemPath = '';

        foreach ($remainder as $index => $element) {
            $itemPath = ($index === 0) ? $element : $itemPath . '.' . $element;

            if (array_key_exists($itemPath, $declared)) {
                $matchedDepth = $index + 1;
            }
        }

        return $matchedDepth;
    }
}
