<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MenAtWork\MultiColumnWizardBundle\MultiColumnWizardBundle;
use Zmyslny\WrapperTags\ZmyslnyWrapperTagsBundle;

final class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ZmyslnyWrapperTagsBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    MultiColumnWizardBundle::class,
                ])
                ->setReplace(['wrapper_tags']),
        ];
    }
}
