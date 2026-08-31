<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Sylament\Schemas\Components\LiveComponent;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host whose schema embeds two keyed children, so one render proves both
 * that a child is mounted and that two embeds off the same partial line keep
 * distinct identities.
 */
#[AsLiveComponent(name: 'EmbedHost', template: '@SchemasFixtures/components/EmbedHost.html.twig')]
class EmbedHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            LiveComponent::make('EmbeddedChild', ['label' => 'first'])
                ->key('first'),
            LiveComponent::make('EmbeddedChild', ['label' => 'second'])
                ->key('second'),
        ]);
    }
}
