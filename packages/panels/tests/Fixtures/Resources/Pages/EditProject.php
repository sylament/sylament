<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ProjectResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The page a relation manager is mounted against: it never renders in this
 * suite, it is the class-string the manager resolves its owner through.
 */
#[AsLiveComponent]
final class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;
}
