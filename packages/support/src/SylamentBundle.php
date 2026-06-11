<?php

declare(strict_types=1);

namespace Sylament\Support;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

abstract class SylamentBundle extends AbstractBundle
{
    protected function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!is_array($bundlesMetadata) || !is_array($bundlesMetadata['FrameworkBundle'] ?? null)) {
            return false;
        }

        $path = $bundlesMetadata['FrameworkBundle']['path'] ?? null;

        return is_string($path) && is_file($path . '/Resources/config/asset_mapper.php');
    }
}
