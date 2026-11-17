<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by one assigned string whose property starts blank, with a prePersist
 * callback that fills a blank label and leaves the key as it is.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CallbackCodedReel
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public string $code = '';

    #[ORM\Column]
    public string $label = '';

    #[ORM\PrePersist]
    public function fillLabel(): void
    {
        if ($this->label === '') {
            $this->label = 'auto';
        }
    }
}
