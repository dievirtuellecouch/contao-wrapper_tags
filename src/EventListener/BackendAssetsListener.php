<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST)]
final class BackendAssetsListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
            return;
        }

        $GLOBALS['TL_CSS']['wrapper_tags'] = 'bundles/zmyslnywrappertags/wrapper-tags-flexible-c44.min.css|static';
        $GLOBALS['TL_JAVASCRIPT']['wrapper_tags'] = 'bundles/zmyslnywrappertags/wrapper-tags.min.js|static';
    }
}
