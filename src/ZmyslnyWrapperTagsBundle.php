<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zmyslny\WrapperTags\DependencyInjection\ZmyslnyWrapperTagsExtension;

final class ZmyslnyWrapperTagsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ZmyslnyWrapperTagsExtension();
    }
}
