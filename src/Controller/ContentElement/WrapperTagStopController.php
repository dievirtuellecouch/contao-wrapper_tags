<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Controller\ContentElement;

use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Zmyslny\WrapperTags\WrapperTagType;

#[AsContentElement(
    type: WrapperTagType::STOP,
    category: 'wrapper_tags',
    template: 'content_element/wrapper_tag_stop',
)]
final class WrapperTagStopController extends AbstractWrapperTagController
{
    protected function getDataField(): string
    {
        return 'wt_closing_tags';
    }
}
