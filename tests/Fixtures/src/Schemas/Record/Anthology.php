<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * A record holding collections in each shape the reader and the mapper tell apart: a Doctrine collection behind an
 * adder and a remover, which log each call to `$calls`; an initialized collection behind a `mixed` property no type
 * names; an array whose items are objects in a docblock alone; and a `Traversable` object. `$editor` is a single
 * related object behind a setter, `$founder` one no setter can change.
 */
final class Anthology
{
    /**
     * @var list<string>
     */
    public array $calls = [];

    public mixed $archive;

    /**
     * @var array<int, ManuscriptWriter>
     */
    public array $drafts = [];

    /**
     * @var ArrayIterator<int, string>|null
     */
    public ?ArrayIterator $index = null;

    /**
     * @var Collection<int, ManuscriptWriter>
     */
    private readonly Collection $contributors;

    private ?ManuscriptWriter $editor = null;

    public function __construct(
        public readonly ManuscriptWriter $founder = new ManuscriptWriter('Founder'),
    ) {
        $this->contributors = new ArrayCollection();
        $this->archive = new ArrayCollection([new ManuscriptWriter('Archived')]);
    }

    /**
     * @return Collection<int, ManuscriptWriter>
     */
    public function getContributors(): Collection
    {
        return $this->contributors;
    }

    public function addContributor(ManuscriptWriter $contributor): void
    {
        $this->calls[] = 'add ' . $contributor->getName();
        $this->contributors->add($contributor);
    }

    public function removeContributor(ManuscriptWriter $contributor): void
    {
        $this->calls[] = 'remove ' . $contributor->getName();
        $this->contributors->removeElement($contributor);
    }

    public function getEditor(): ?ManuscriptWriter
    {
        return $this->editor;
    }

    public function setEditor(?ManuscriptWriter $editor): void
    {
        $this->editor = $editor;
    }
}
