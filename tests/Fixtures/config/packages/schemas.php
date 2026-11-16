<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\Doctrine\BinaryUuidType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CaselessType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CipherType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\HexCodeType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\MarkedCodeType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherBigIntType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherBooleanType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherDecimalType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\VoucherGuidType;
use Sylament\Tests\Fixtures\Schemas\Doctrine\WithdrawnNoteFilter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'types' => [
                BinaryUuidType::NAME => BinaryUuidType::class,
                CaselessType::NAME => CaselessType::class,
                CipherType::NAME => CipherType::class,
                HexCodeType::NAME => HexCodeType::class,
                MarkedCodeType::NAME => MarkedCodeType::class,
                VoucherBigIntType::NAME => VoucherBigIntType::class,
                VoucherBooleanType::NAME => VoucherBooleanType::class,
                VoucherDecimalType::NAME => VoucherDecimalType::class,
                VoucherGuidType::NAME => VoucherGuidType::class,
            ],
        ],
        'orm' => [
            'entity_managers' => [
                'default' => [
                    'filters' => [
                        WithdrawnNoteFilter::NAME => [
                            'class' => WithdrawnNoteFilter::class,
                            'enabled' => false,
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
