<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Greenhouse;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Planter;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * Where a taken scalar pair's message lands on a real form: under every key
 * field a component maps — so a half the form hides carries it too, and only
 * the visible half shows it — and, inside a relationship repeater, under the
 * halves of each taken item. Planter is keyed by `bed` + `slot`. Nothing is
 * flushed after a refusal, on any platform.
 */

/**
 * @param list<object> $components
 *
 * @return array{Schema, FieldRenderHost}
 */
function takenPairWire(object $record, array $components): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

/**
 * @return array<string, list<string>>|null
 */
function takenPairRefusal(Schema $schema): ?array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return null;
}

it('shows a taken pair under the visible half when the other is hidden', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $stored = new Planter();
    $stored->setBed('north');
    $stored->setSlot('1');
    $stored->setCrop('basil');

    $entityManager->persist($stored);
    $entityManager->flush();
    $entityManager->clear();

    $planter = new Planter();
    $planter->setSlot('1');

    [$schema, $host] = takenPairWire($planter, [
        TextInput::make('bed'),
        TextInput::make('slot')->hidden(),
        TextInput::make('crop'),
    ]);

    $host->data['bed'] = 'north';
    $host->data['crop'] = 'thyme';

    expect(takenPairRefusal($schema))->toBe([
        'data.bed' => ['This value is already used.'],
        'data.slot' => ['This value is already used.'],
    ])
        ->and($host->getErrorsForSchemaStatePath('data.bed'))->toBe(['This value is already used.'])
        ->and(mb_substr_count(renderToHtml($schema), '<li class="sy-fo-field-error-message">This value is already used.</li>'))->toBe(1)
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT bed, slot, crop FROM Planter'))->toBe([['bed' => 'north', 'slot' => '1', 'crop' => 'basil']]);
});

it('files each taken pair of a relationship repeater under the halves of its own item', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $greenhouse = new Greenhouse();

    [$schema, $host] = takenPairWire($greenhouse, [
        TextInput::make('name'),
        Repeater::make('planters')->relationship()->components([TextInput::make('bed'), TextInput::make('slot'), TextInput::make('crop')]),
    ]);

    $host->data['name'] = 'North';
    $host->data['planters'] = [
        'new-a' => ['bed' => 'north', 'slot' => '1', 'crop' => 'basil'],
        'new-b' => ['bed' => 'north', 'slot' => '1', 'crop' => 'thyme'],
        'new-c' => ['bed' => 'south', 'slot' => '2', 'crop' => 'mint'],
        'new-d' => ['bed' => 'south', 'slot' => '2', 'crop' => 'sage'],
    ];

    expect(takenPairRefusal($schema))->toBe([
        'data.planters.new-b.bed' => ['This value is already used.'],
        'data.planters.new-b.slot' => ['This value is already used.'],
        'data.planters.new-d.bed' => ['This value is already used.'],
        'data.planters.new-d.slot' => ['This value is already used.'],
    ])
        ->and(mb_substr_count(renderToHtml($schema), '<li class="sy-fo-field-error-message">This value is already used.</li>'))->toBe(4)
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT bed, slot FROM Planter'))->toBe([]);
});

it('files a taken pair under the one half a relationship repeater item maps and nothing at the item itself', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $stored = new Planter();
    $stored->setBed('north');
    $stored->setSlot('1');
    $stored->setCrop('basil');

    $entityManager->persist($stored);
    $entityManager->flush();
    $entityManager->clear();

    $planter = new Planter();
    $planter->setBed('south');
    $planter->setSlot('1');

    $greenhouse = new Greenhouse();
    $greenhouse->addPlanter($planter);

    [$schema, $host] = takenPairWire($greenhouse, [
        TextInput::make('name'),
        Repeater::make('planters')->relationship()->components([TextInput::make('bed'), TextInput::make('crop')]),
    ]);

    $itemKey = array_key_first($host->data['planters']);

    $host->data['name'] = 'North';
    $host->data['planters'][$itemKey]['bed'] = 'north';
    $host->data['planters'][$itemKey]['crop'] = 'thyme';

    expect(takenPairRefusal($schema))->toBe([
        'data.planters.' . $itemKey . '.bed' => ['This value is already used.'],
    ])
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT bed, slot, crop FROM Planter'))->toBe([['bed' => 'north', 'slot' => '1', 'crop' => 'basil']]);
});
