<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Controller\ContentElement;

use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Zmyslny\WrapperTags\WrapperTagType;

#[AsContentElement(
    type: WrapperTagType::COMPLETE,
    category: 'wrapper_tags',
    template: 'content_element/wrapper_tag_complete',
)]
final class WrapperTagCompleteController extends AbstractWrapperTagController
{
    protected function getDataField(): string
    {
        return 'wt_complete_tags';
    }

    protected function includesVoidFlag(): bool
    {
        return true;
    }
}
