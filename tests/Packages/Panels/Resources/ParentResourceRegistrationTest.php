<?php

declare(strict_types=1);

use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Tests\Fixtures\Panels\Resources\GardenResource;
use Sylament\Tests\Fixtures\Panels\Resources\GardenSeedlingResource;

it('names no relationship until told, and answers a named copy from relationship(), leaving itself unnamed', function (): void {
    $registration = new ParentResourceRegistration(GardenResource::class, GardenSeedlingResource::class);

    $named = $registration->relationship('seedlings');

    expect($named->getRelationshipName())->toBe('seedlings')
        ->and($named->parentResource)->toBe(GardenResource::class)
        ->and($named->childResource)->toBe(GardenSeedlingResource::class)
        ->and($registration->getRelationshipName())->toBeNull();
});
