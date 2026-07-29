<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanBeMountedFixture;
use Sylament\Schemas\Schema;

it('default getMountUsing() fills a schema when one is mounted', function (): void {
    $fixture = new CanBeMountedFixture();
    $schema = new class extends Schema {
        public bool $wasFilled = false;

        public object|array|null $filledWith = null;

        public function fill(object|array|null $state = null): static
        {
            $this->wasFilled = true;
            $this->filledWith = $state;

            return $this;
        }
    };

    $fixture->mount(['schema' => $schema]);

    expect($schema->wasFilled)->toBeTrue()
        ->and($schema->filledWith)->toBeNull();
});

it('default getMountUsing() is a safe no-op without a schema', function (): void {
    $fixture = new CanBeMountedFixture();

    expect($fixture->mount([]))->toBeNull();
});

it('a custom mountUsing() callback wins over the default', function (): void {
    $fixture = new CanBeMountedFixture();
    $fixture->mountUsing(static fn (): string => 'custom');

    expect($fixture->mount([]))->toBe('custom');
});

it('fillForm() fills the mounted schema with a literal array', function (): void {
    $fixture = new CanBeMountedFixture();
    $fixture->fillForm(['title' => 'Hi']);

    $schema = new class extends Schema {
        public object|array|null $filledWith = null;

        public function fill(object|array|null $state = null): static
        {
            $this->filledWith = $state;

            return $this;
        }
    };

    $fixture->mount(['schema' => $schema]);

    expect($schema->filledWith)->toBe(['title' => 'Hi']);
});

it('fillForm() fills the mounted schema with data resolved from a closure', function (): void {
    $fixture = new CanBeMountedFixture();
    $fixture->fillForm(static fn (): array => ['title' => 'From closure']);

    $schema = new class extends Schema {
        public object|array|null $filledWith = null;

        public function fill(object|array|null $state = null): static
        {
            $this->filledWith = $state;

            return $this;
        }
    };

    $fixture->mount(['schema' => $schema]);

    expect($schema->filledWith)->toBe(['title' => 'From closure']);
});
