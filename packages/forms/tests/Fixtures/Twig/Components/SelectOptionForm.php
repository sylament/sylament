<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host carrying one relationship select whose option actions run through
 * the real mounted-action pipeline: the trigger emits the field's absolute key as
 * `context['schemaComponent']`, the modal schema lands on
 * `mountedActionsData.0`, and the created or edited row reaches the fixture
 * database. The props pick the arm under test — which association the select is
 * bound to, whether it speaks a list, and whether it is a relationship select at
 * all — so one fixture covers every gate rather than one host per case.
 */
#[AsLiveComponent(name: 'SelectOptionForm', template: '@FormsFixtures/components/SelectOptionForm.html.twig')]
class SelectOptionForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public string $relationship = 'author';

    #[LiveProp]
    public bool $isMultiple = false;

    #[LiveProp]
    public bool $isDisabled = false;

    #[LiveProp]
    public bool $hasRelationship = true;

    public function mount(): void
    {
        $this->fill();
    }

    public function schema(Schema $schema): Schema
    {
        $select = Select::make($this->relationship)
            ->multiple($this->isMultiple)
            ->disabled($this->isDisabled)
            ->manageOptionForm([TextInput::make('name')]);

        if ($this->hasRelationship) {
            $select->relationship(titleAttribute: 'name');
        } else {
            $select->options(['a' => 'A']);
        }

        return $schema
            ->statePath('data')
            ->record(Post::class)
            ->components([$select]);
    }
}
