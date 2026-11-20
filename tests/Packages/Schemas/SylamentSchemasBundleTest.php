<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Colors\Components\IconComponent;
use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\SylamentSchemasBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

it('registers every component, at each level under Components/, and the schema as services, so make() builds each through the container', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/schemas/src/Components';
    $classes = [Schema::class];
    $directories = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($directories as $file) {
        $relative = mb_substr($file->getPathname(), mb_strlen($root) + 1, -4);

        if (preg_match('#^(Concerns|Contracts)/#', $relative) === 1) {
            continue;
        }

        $class = 'Sylament\Schemas\Components\\' . str_replace('/', '\\', $relative);

        if (!new ReflectionClass($class)->isAbstract()) {
            $classes[] = $class;
        }
    }

    expect($classes)->toContain(Section::class, Tab::class, Step::class)
        ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
});

it('registers every colour policy public, so ColorManager resolves it by class-string', function (): void {
    $policies = array_map(
        static fn (string $path): string => 'Sylament\Schemas\Colors\Components\\' . basename($path, '.php'),
        glob(dirname(__DIR__, 3) . '/packages/schemas/src/Colors/Components/*.php') ?: [],
    );

    $container = $this::getContainer()->get('service_container');

    expect($policies)->toContain(IconComponent::class, TextComponent::class)
        ->and(array_values(array_filter($policies, fn (string $policy): bool => !$container->has($policy))))->toBe([]);
});

it('registers its property accessor, record type reader and association reader under their sylament_schemas ids, each aliased by its class', function (): void {
    $container = $this::getContainer();

    expect($container->get('sylament_schemas.property_accessor'))->toBe($container->get(PropertyAccessor::class))
        ->and($container->get('sylament_schemas.record_type_reader'))->toBe($container->get(RecordTypeReader::class))
        ->and($container->get('sylament_schemas.association_reader'))->toBe($container->get(AssociationReader::class));
});

it("wires its association reader's entity manager null-on-invalid", function (): void {
    $container = new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test']));
    new SylamentSchemasBundle()->getContainerExtension()?->load([], $container);

    $manager = $container->getDefinition('sylament_schemas.association_reader')->getArgument(0);

    expect($manager)->toBeInstanceOf(Reference::class)
        ->and((string) $manager)->toBe(EntityManagerInterface::class)
        ->and($manager->getInvalidBehavior())->toBe(ContainerInterface::NULL_ON_INVALID_REFERENCE);
});

it('serves its built controllers and the utilities forms imports through AssetMapper as @sylament/schemas', function (): void {
    $assets = $this::getContainer()->get(AssetMapperInterface::class);
    $dist = dirname(__DIR__, 3) . '/packages/schemas/assets/dist';

    expect($assets->getAssetFromSourcePath("{$dist}/controllers/tabs_controller.js")?->logicalPath)->toBe('@sylament/schemas/controllers/tabs_controller.js')
        ->and($assets->getAssetFromSourcePath("{$dist}/utils/schema_component_method.js")?->logicalPath)->toBe('@sylament/schemas/utils/schema_component_method.js');
});
