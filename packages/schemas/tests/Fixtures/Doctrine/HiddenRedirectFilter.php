<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;

final class HiddenRedirectFilter extends SQLFilter
{
    /**
     * @param ClassMetadata<object> $targetEntity
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        return $targetEntity->getName() === Redirect::class
            ? sprintf("%s.label <> 'hidden'", $targetTableAlias)
            : '';
    }
}
