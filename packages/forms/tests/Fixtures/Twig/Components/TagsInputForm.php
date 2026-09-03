<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\TagsInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host carrying the tags input's three arms, so the write the
 * controller makes — a whole array onto one state path — can be played through a
 * real round-trip: `tags` rests as a list, `keywords` folds its list onto a
 * separator-joined string at the boundary, and `topics` is live, so its
 * `afterStateUpdated` sees the list the client just wrote.
 */
#[AsLiveComponent(name: 'TagsInputForm', template: '@FormsFixtures/components/TagsInputForm.html.twig')]
class TagsInputForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The dehydrated bag `getState()` assembles on `save`, held as a LiveProp so it
     * survives the round-trip and can be read back in the harness.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedState = [];

    /**
     * The list `topics`' `afterStateUpdated` was handed, in the same shape.
     *
     * @var array<mixed>|null
     */
    #[LiveProp]
    public ?array $updatedTopics = null;

    /**
     * @param array<string, mixed>|null $data
     */
    public function mount(?array $data = null): void
    {
        // fill(null) rests every arm at its `default([])`; a seeded bag is filled as
        // state instead, so `hydrateTags()` runs against what the record would hold.
        $this->fill($data);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TagsInput::make('tags'),
                TagsInput::make('keywords')->separator(','),
                TagsInput::make('topics')
                    ->live()
                    ->afterStateUpdated(function (mixed $state): void {
                        $this->updatedTopics = is_array($state) ? $state : null;
                    }),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }
}
