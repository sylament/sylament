<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\Attribute\Required;

trait CanAccessProperties
{
    private PropertyAccessor $propertyAccessor;

    #[Required]
    public function setPropertyAccessor(PropertyAccessor $propertyAccessor): void
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor ??= new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
    }
}
