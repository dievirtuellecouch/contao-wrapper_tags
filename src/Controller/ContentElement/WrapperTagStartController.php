<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Controller\ContentElement;

use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Zmyslny\WrapperTags\WrapperTagType;

#[AsContentElement(
    type: WrapperTagType::START,
    category: 'wrapper_tags',
    template: 'content_element/wrapper_tag_start',
)]
final class WrapperTagStartController extends AbstractWrapperTagController
{
    protected function getDataField(): string
    {
        return 'wt_opening_tags';
    }
}
