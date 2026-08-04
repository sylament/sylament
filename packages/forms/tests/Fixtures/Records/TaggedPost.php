<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

use ArrayObject;

/**
 * The canonical Doctrine collection shape a repeater refuses at hydration: a
 * Traversable-typed property behind an adder, which is what makes
 * ReflectionExtractor resolve `tags` mutator-first to `list<PostTag>` — a
 * collection of objects, never a value-shaped array.
 */
final readonly class TaggedPost
{
    /** @var ArrayObject<int, PostTag> */
    private ArrayObject $tags;

    public function __construct()
    {
        $this->tags = new ArrayObject();
    }

    /**
     * @return ArrayObject<int, PostTag>
     */
    public function getTags(): ArrayObject
    {
        return $this->tags;
    }

    public function addTag(PostTag $tag): void
    {
        $this->tags->append($tag);
    }
}
