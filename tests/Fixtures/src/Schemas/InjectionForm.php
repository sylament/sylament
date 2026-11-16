<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use DateTimeInterface;
use Stringable;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A schema bound to a {@see Memo} and given no operation, whose own `components()`
 * closure lists what the schema hands it, by name, by the record's class or interface,
 * and under a type the record is not; a text names the operation it falls back to.
 */
#[AsLiveComponent(name: 'Schemas:InjectionForm', template: '@Fixtures/schemas/InjectionForm.html.twig')]
class InjectionForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record(new Memo('Minutes'))
            ->components(static fn (Schema $container, $liveComponent, $record, Memo $memo, Stringable $note, ?DateTimeInterface $date = null): array => [
                Text::make(($container->getKey() === 'form') ? 'Container form' : 'Another container'),
                Text::make(($liveComponent instanceof self) ? 'Hosted here' : 'Hosted elsewhere'),
                Text::make(($record instanceof Memo) ? "Record {$record->subject}" : 'No record'),
                Text::make("Memo {$memo->subject}"),
                Text::make("Note {$note}"),
                Text::make(($date instanceof DateTimeInterface) ? 'A date' : 'No date'),
                Text::make(static fn (string $operation): string => "Run as {$operation}"),
            ]);
    }
}
