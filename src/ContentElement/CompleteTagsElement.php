<?php

/**
 * Copyright (C) 2018 Zmyslni
 *
 * @author  Ostrowski Maciej <http://contao-developer.pl>
 * @author  Ostrowski Maciej <maciek@zmyslni.pl>
 * @license LGPL-3.0+
 */

namespace Zmyslny\WrapperTags\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\System;
use Zmyslny\WrapperTags\Util\TagNormalizer;

class CompleteTagsElement extends ContentElement
{
    protected $strTemplate = 'ce_wt_complete_tags';

    private function isBackendRequest(): bool
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        return $request && $request->attributes->get('_scope') === 'backend';
    }

    public function generate()
    {
        $this->wt_complete_tags = TagNormalizer::normalize($this->wt_complete_tags, true);

        if ($this->isBackendRequest()) {
            $template = new BackendTemplate('be_wildcard_complete_tags');
            $template->wildcard = '### ' . $GLOBALS['TL_LANG']['CTE']['wt_complete_tags'][0] . ' (id:' . $this->id . ') ###';
            $template->tags = $this->wt_complete_tags;
            $ver = \defined('VERSION') ? \constant('VERSION') : '5.3';
            $template->version = version_compare($ver, '3.5', '>') ? 'version-over-35' : 'version-35';
            return $template->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $tags = TagNormalizer::normalize($this->wt_complete_tags, true);

        foreach ($tags as $i => $tag) {
            if ($tag['attributes']) {
                foreach ($tag['attributes'] as $t => $attribute) {
                    $attribute['name'] = System::getContainer()->get('contao.insert_tag.parser')->replace($attribute['name']);
                    $tags[$i]['attributes'][$t] = $attribute;
                }
            }
        }

        $this->Template->tags = $tags;
    }
}
