<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zmyslny\WrapperTags\Util\TagNormalizer;

abstract class AbstractWrapperTagController extends AbstractContentElementController
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
    ) {
    }

    protected function getResponse(
        FragmentTemplate $template,
        ContentModel $model,
        Request $request,
    ): Response {
        $tags = TagNormalizer::normalizeForRendering(
            $model->{$this->getDataField()},
            $this->includesVoidFlag(),
        );

        foreach ($tags as &$tag) {
            foreach ($tag['attributes'] as $attributeIndex => &$attribute) {
                $attribute['name'] = $this->insertTagParser->replace($attribute['name']);

                if (!TagNormalizer::isValidAttributeName($attribute['name'])) {
                    unset($tag['attributes'][$attributeIndex]);
                }
            }
            unset($attribute);

            $tag['attributes'] = array_values($tag['attributes']);
        }
        unset($tag);

        $template->set('tags', $tags);

        return $template->getResponse();
    }

    abstract protected function getDataField(): string;

    protected function includesVoidFlag(): bool
    {
        return false;
    }
}
