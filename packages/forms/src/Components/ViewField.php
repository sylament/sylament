<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use LogicException;

class ViewField extends Field
{
    public function getTemplate(): string
    {
        return parent::getTemplate() ?? throw new LogicException(sprintf(
            'The [%s] field has no template. Call template() on it, or declare a $template property on a subclass.',
            static::class,
        ));
    }
}
