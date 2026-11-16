<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of an {@see Atelier}'s plain apprentices, with two unidirectional to-ones
 * onto a composite-keyed {@see OrderLine}: one whose second join column refuses null,
 * one whose columns all accept it.
 */
#[ORM\Entity]
class AtelierApprentice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'apprentices')]
        public ?Atelier $atelier = null,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(name: 'commission_order_number', referencedColumnName: 'order_number')]
        #[ORM\JoinColumn(name: 'commission_line_number', referencedColumnName: 'line_number', nullable: false)]
        public ?OrderLine $commission = null,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(name: 'quote_order_number', referencedColumnName: 'order_number')]
        #[ORM\JoinColumn(name: 'quote_line_number', referencedColumnName: 'line_number')]
        public ?OrderLine $quote = null,
    ) {
    }
}
