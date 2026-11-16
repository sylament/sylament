<?php

declare(strict_types=1);

use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\UnnamedSchemaHost;

describe('getLiveComponent()', function (): void {
    it('returns the host a schema is bound to, and throws once it is unbound', function (): void {
        $host = new UnnamedSchemaHost();
        $schema = Schema::make($host);

        expect($schema->getLiveComponent())->toBe($host)
            ->and(fn (): InteractsWithSchemas => $schema->liveComponent()->getLiveComponent())->toThrow(RuntimeException::class, 'Schema is not part of LiveComponent');
    });

    it("hands an unbound schema's closures a null live component rather than throwing", function (): void {
        $schema = Schema::make();

        expect($schema->evaluate(static fn (?InteractsWithSchemas $liveComponent): string => $liveComponent instanceof InteractsWithSchemas ? 'bound' : 'unbound'))->toBe('unbound');
    });
});
