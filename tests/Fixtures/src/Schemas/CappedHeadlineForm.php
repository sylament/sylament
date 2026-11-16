<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A create form over a {@see CappedHeadline}: `save` maps the form onto a new record and
 * flushes it, so the record's own constraints answer the submit.
 */
#[AsLiveComponent(name: 'Schemas:CappedHeadlineForm', template: '@Fixtures/schemas/CappedHeadlineForm.html.twig')]
class CappedHeadlineForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record(CappedHeadline::class)
            ->components([
                TextInput::make('code'),
                TextInput::make('title'),
                TextInput::make('slug'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->entityManager->persist($this->getSchema('form')->getData());
        $this->entityManager->flush();
    }
}
