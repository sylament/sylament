<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Source\FilamentDeprecations;

/**
 * Sylament ships nothing Filament marks `@deprecated` (`docs/progress/renames.md`, "A deprecated Filament member is
 * not ported"). Each line is one name still to be replaced by the one Filament names, kept as text so it stays one
 * name per line; regenerate it from `FilamentDeprecations::shipped()`.
 */
const FILAMENT_DEPRECATIONS_REMAINING = <<<'LIST'
Sylament\Tables\Columns\Concerns\HasRowLoopObject
Sylament\Tables\Columns\Summarizers\Concerns\InteractsWithTableQuery::hasQueryModification
Sylament\Tables\Concerns\CanDeferLoading
Sylament\Tables\Filters\Concerns\HasSchema
Sylament\Tables\Filters\Concerns\HasSchema::getSchema
Sylament\Tables\Filters\Concerns\HasSchema::getSchemaComponents
Sylament\Tables\Filters\Concerns\HasSchema::hasSchema
Sylament\Tables\Filters\Concerns\HasSchema::schema
Sylament\Tables\Table\Concerns\CanDeferLoading
Sylament\Tables\Table\Concerns\CanDeferLoading::deferLoading
Sylament\Tables\Table\Concerns\CanDeferLoading::isLoadingDeferred
Sylament\Tables\Table\Concerns\HasBulkActions::bulkActions
Sylament\Tables\Table\Concerns\HasBulkActions::pushBulkActions
LIST;

it('finds a member Filament deprecates on its Sylament counterpart', function (): void {
    $filament = '<?php namespace Filament\X; class Field { /** @deprecated Use `b()` instead. */ public function a() {} public function b() {} }';
    $sylament = '<?php namespace Sylament\X; class Field { public function a() {} public function b() {} }';

    expect(FilamentDeprecations::matches($filament, $sylament))->toBe(['Sylament\X\Field::a']);
});

it('finds a class Filament deprecates', function (): void {
    $filament = '<?php namespace Filament\X; /** @deprecated Use `Y` instead. */ trait OldThing {}';
    $sylament = '<?php namespace Sylament\X; trait OldThing {}';

    expect(FilamentDeprecations::matches($filament, $sylament))->toBe(['Sylament\X\OldThing']);
});

it('finds a deprecated member under the trait that declares it, through the traits on either side', function (): void {
    $filament = '<?php namespace Filament\X; trait HasOld { /** @deprecated */ public $old; /** @deprecated */ public function old() {} } class Field { use HasOld; }';
    $sylament = '<?php namespace Sylament\X\Concerns; trait CanBeOld { public $old; public function old() {} } namespace Sylament\X; class Field { use Concerns\CanBeOld; } class Other { use Concerns\CanBeOld; }';

    expect(FilamentDeprecations::matches($filament, $sylament))->toBe(['Sylament\X\Concerns\CanBeOld::$old', 'Sylament\X\Concerns\CanBeOld::old']);
});

it('passes a member Filament redeclares undeprecated below the class that deprecates it', function (): void {
    $filament = '<?php namespace Filament\X; class Component { /** @deprecated */ public function name() {} } trait HasName { public function name() {} } class Field extends Component { use HasName; }';
    $sylament = '<?php namespace Sylament\X; class Field { public function name() {} }';

    expect(FilamentDeprecations::matches($filament, $sylament))->toBe([]);
});

it('matches a class the Renames table pairs with a Filament class of another name', function (): void {
    $renames = '| `Filament\X\Concerns\InteractsWithThings` | `HasThings` | trait | `Sylament\X\Concerns\HasThings` | Swapped. |';
    $filament = '<?php namespace Filament\X\Concerns; trait InteractsWithThings { /** @deprecated */ public function old() {} } trait HasThings { public function old() {} }';
    $sylament = '<?php namespace Sylament\X\Concerns; trait HasThings { public function old() {} }';

    expect(FilamentDeprecations::matches($filament, $sylament, FilamentDeprecations::renamedCounterparts($renames)))->toBe(['Sylament\X\Concerns\HasThings::old']);
});

it('ships only what is still to be removed', function (): void {
    expect(FilamentDeprecations::shipped())->toBe(preg_split("/\n/", FILAMENT_DEPRECATIONS_REMAINING, flags: PREG_SPLIT_NO_EMPTY));
});
