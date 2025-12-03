<?php

namespace Zmyslny\WrapperTags\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Zmyslny\WrapperTags\ZmyslnyWrapperTagsBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ZmyslnyWrapperTagsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}

