<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

use ArrayObject;

/**
 * The canonical Doctrine collection shape: a Traversable-typed property behind
 * an adder, initialized in the constructor. The adder is what makes
 * ReflectionExtractor resolve `tags` mutator-first to `list<PostTag>` — a
 * CollectionType, never the getter's ObjectType.
 *
 * @phpstan-type Tags ArrayObject<int, PostTag>
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
