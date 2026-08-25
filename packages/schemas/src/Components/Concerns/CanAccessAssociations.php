<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Sylament\Schemas\State\AssociationReader;
use Symfony\Contracts\Service\Attribute\Required;

trait CanAccessAssociations
{
    private AssociationReader $associationReader;

    #[Required]
    public function setAssociationReader(AssociationReader $associationReader): void
    {
        $this->associationReader = $associationReader;
    }

    public function getAssociationReader(): AssociationReader
    {
        return $this->associationReader ??= new AssociationReader();
    }
}
