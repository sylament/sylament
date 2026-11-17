<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\Radio;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One radio per way a radio reads, casts and guards its state: a static map with a
 * description and a disabled option, int keys, a required `boolean()`, int keys a custom
 * cast stores as booleans, a backed enum, an `in()` list with no options, one with nothing
 * to enumerate and one whose options name no enum. A save validates every field, so a
 * case asserts the errors of the field it fills.
 */
#[AsLiveComponent(name: 'Forms:RadioForm', template: '@Fixtures/forms/RadioForm.html.twig')]
class RadioForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function mount(): void
    {
        $this->getSchema('form')->fill();
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Radio::make('plan')
                    ->options(['basic' => 'Basic', 'pro' => 'Pro', 'legacy' => 'Legacy'])
                    ->descriptions(['pro' => 'For teams'])
                    ->disableOptionWhen(static fn (string $value): bool => $value === 'legacy'),
                Radio::make('priority')->options([1 => 'Low', 2 => 'High']),
                Radio::make('subscribed')->boolean()->required(),
                Radio::make('confirmed')->options([1 => 'Yes', 0 => 'No'])->stateCast(new BooleanStateCast()),
                Radio::make('mode')->enum(DeliveryMode::class),
                Radio::make('restricted')->in(['yew']),
                Radio::make('unusable'),
                // @phpstan-ignore argument.type (a string that names no enum on purpose: the options the field then offers)
                Radio::make('dated')->options('date'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
