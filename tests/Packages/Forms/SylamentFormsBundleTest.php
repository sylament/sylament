<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\TextInput\Actions\ShowPasswordAction;
use Sylament\Forms\SylamentFormsBundle;
use Sylament\Forms\Validation\UniqueValueValidator;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

describe('view namespace', function (): void {
    it('maps @SylamentForms to the package templates with no twig path configured, so a field renders its partial', function (): void {
        $paths = $this::getContainer()->get('twig.loader.native_filesystem')->getPaths('SylamentForms');

        expect(array_map(realpath(...), $paths))->toBe([dirname(__DIR__, 3) . '/packages/forms/templates'])
            ->and($this->renderSchemaComponent(TextInput::make('code')))->toContainElement(Selectors::FIELD_WRAPPER . ' input');
    });
});

describe('component services', function (): void {
    it('registers every component at each level its glob names, so make() builds each through the container', function (): void {
        $root = dirname(__DIR__, 3) . '/packages/forms/src/Components';
        $classes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $relative = mb_substr($file->getPathname(), mb_strlen($root) + 1, -4);

            if (preg_match('#^(Concerns|Contracts)/#', $relative) === 1) {
                continue;
            }

            $class = 'Sylament\Forms\Components\\' . str_replace('/', '\\', $relative);

            if (!new ReflectionClass($class)->isAbstract()) {
                $classes[] = $class;
            }
        }

        expect($classes)->toContain(TextInput::class, TableColumn::class, ShowPasswordAction::class)
            ->and(array_values(array_filter($classes, fn (string $class): bool => !$this::getContainer()->has($class))))->toBe([]);
    });

    it('builds a new field on every make(), so two fields made from one class keep their own configuration', function (): void {
        $first = TextInput::make('first');
        $second = TextInput::make('second');

        expect($first->getName())->toBe('first')
            ->and($second->getName())->toBe('second');
    });

    it("replaces an affix action's icon by the application's alias, since the glob builds the action through the container", function (): void {
        $this::bootKernel(['environment' => 'registered_icons']);

        expect($this->renderSchemaComponent(TextInput::make('secret')->password()->revealable()))
            ->toContainElement(Selectors::SHOW_PASSWORD_BUTTON . Selectors::textInputAction('reveal') . ' svg', 'Star');
    });
});

describe('validator services', function (): void {
    it('registers every constraint validator as a service, so one needing a service is built by the container', function (): void {
        $validators = array_map(
            static fn (string $path): string => 'Sylament\Forms\Validation\\' . basename($path, '.php'),
            glob(dirname(__DIR__, 3) . '/packages/forms/src/Validation/*Validator.php') ?: [],
        );

        expect($validators)->toContain(UniqueValueValidator::class)
            ->and(array_values(array_filter($validators, fn (string $validator): bool => !$this::getContainer()->has($validator))))->toBe([]);
    });
});

describe('temporary upload store', function (): void {
    it('hands the store the storage, directory and sweep its sylament_forms config names', function (): void {
        $extension = new SylamentFormsBundle()->getContainerExtension();
        $container = new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test']));

        $extension?->load([['temporary_file_upload' => ['disk' => 'secondary.storage', 'directory' => 'inbox', 'cleanup' => false]]], $container);

        expect($container->getDefinition('sylament_forms.temporary_uploads')->getArguments())
            ->toMatchArray(['$diskName' => 'secondary.storage', '$directory' => 'inbox', '$cleanup' => false]);
    });
});

describe('asset registration', function (): void {
    it('serves its built controllers, a per-file one and a bundled one, through AssetMapper as @sylament/forms', function (): void {
        $assets = $this::getContainer()->get(AssetMapperInterface::class);
        $dist = dirname(__DIR__, 3) . '/packages/forms/assets/dist';

        expect($assets->getAssetFromSourcePath("{$dist}/controllers/checkbox_list_controller.js")?->logicalPath)->toBe('@sylament/forms/controllers/checkbox_list_controller.js')
            ->and($assets->getAssetFromSourcePath("{$dist}/controllers/select_controller.js")?->logicalPath)->toBe('@sylament/forms/controllers/select_controller.js');
    });
});
