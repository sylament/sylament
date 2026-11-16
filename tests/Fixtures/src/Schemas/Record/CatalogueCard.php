<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Stringable;

/**
 * A plain record with every shape the mapping edges treat differently: scalars, a nested
 * object built when null, an array-valued branch, a typed property never assigned, an
 * enum, a date, an object collection, an intermediate whose class needs arguments and one
 * no class can fill.
 */
class CatalogueCard
{
    public ?string $title = null;

    public ?string $shelfMark = null;

    public ?CardAuthor $author = null;

    /**
     * @var array<string, mixed>
     */
    public array $meta = [];

    public string $unassigned;

    public ?CardShade $shade = null;

    public ?DateTimeImmutable $catalogued = null;

    public ?CardBinding $binding = null;

    public ?Stringable $label = null;

    /**
     * @var Collection<int, CardAuthor>
     */
    public Collection $contributors;

    public function __construct()
    {
        $this->contributors = new ArrayCollection();
    }
}
