<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A sheet of {@see VettedBinder::$sheets}: a capped heading a form maps, and a status it
 * leaves alone, which refuses a draft.
 */
#[ORM\Entity]
class VettedBinderSheet
{
    public const string DRAFT = 'Draft';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'sheets')]
    public ?VettedBinder $binder = null;

    public function __construct(
        #[ORM\Column(length: 32)]
        #[Assert\Length(max: 5, maxMessage: 'A sheet heading is too long.')]
        public string $heading = '',
        #[ORM\Column(length: 16)]
        #[Assert\NotEqualTo(self::DRAFT, message: 'A sheet cannot stay a draft.')]
        public string $status = 'Filed',
    ) {
    }
}
