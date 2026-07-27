<?php

declare(strict_types=1);

namespace Sylament\Support\Components;

use Closure;
use ReflectionMethod;
use Sylament\Support\DependencyInjection\HasStaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;

class ComponentManager implements StaticInstanceInterface
{
    use HasStaticInstance;

    /**
     * @var array<class-string, array<int, Closure>>
     */
    protected array $configurations = [];

    /**
     * @var array<class-string, array<int, Closure>>
     */
    protected array $importantConfigurations = [];

    /**
     * @var array<class-string, array<class-string>>
     */
    protected array $configureClassHierarchyCache = [];

    /**
     * @var array<class-string, class-string>
     */
    protected array $setUpMethodClassCache = [];

    final public function __construct()
    {
    }

    public static function resolve(): static
    {
        if (!self::$instance instanceof static) {
            static::setInstance(new static());
        }

        return static::getInstance();
    }

    /**
     * Registers a closure applied to every subsequently configured instance of the class.
     * Returns a closure that removes the registration, or, when `$during` is given, keeps
     * the registration only while `$during()` runs and returns its result.
     *
     * @param class-string $component
     */
    public function configureUsing(string $component, Closure $modifyUsing, ?Closure $during = null, bool $isImportant = false): mixed
    {
        if ($isImportant) {
            $this->importantConfigurations[$component] ??= [];
            $this->importantConfigurations[$component][] = $modifyUsing;
        } else {
            $this->configurations[$component] ??= [];
            $this->configurations[$component][] = $modifyUsing;
        }

        if (!$during instanceof Closure) {
            $configurationKey = $isImportant
                ? array_key_last($this->importantConfigurations[$component])
                : array_key_last($this->configurations[$component]);

            return function () use ($component, $configurationKey, $isImportant): void {
                if ($isImportant) {
                    unset($this->importantConfigurations[$component][$configurationKey]);

                    return;
                }

                unset($this->configurations[$component][$configurationKey]);
            };
        }

        try {
            return $during();
        } finally {
            if ($isImportant) {
                array_pop($this->importantConfigurations[$component]);
            } else {
                array_pop($this->configurations[$component]);
            }
        }
    }

    /**
     * Walks the component's class hierarchy root-first, calling `$setUp` when the class
     * declaring `setUp()` is reached and each class's registered configuration closures
     * after it; important configuration closures run last, again in hierarchy order.
     */
    public function configure(Component $component, Closure $setUp): void
    {
        $componentClass = $component::class;

        $classesToConfigure = $this->configureClassHierarchyCache[$componentClass]
            ??= [...array_reverse(class_parents($component)), $componentClass];

        $setUpMethodClass = $this->setUpMethodClassCache[$componentClass]
            ??= new ReflectionMethod($component, 'setUp')->getDeclaringClass()->getName();

        foreach ($classesToConfigure as $classToConfigure) {
            if ($classToConfigure === $setUpMethodClass) {
                $setUp();
            }

            if (array_key_exists($classToConfigure, $this->configurations)) {
                foreach ($this->configurations[$classToConfigure] as $configure) {
                    $configure($component);
                }
            }
        }

        foreach ($classesToConfigure as $classToConfigure) {
            if (array_key_exists($classToConfigure, $this->importantConfigurations)) {
                foreach ($this->importantConfigurations[$classToConfigure] as $configure) {
                    $configure($component);
                }
            }
        }
    }
}
