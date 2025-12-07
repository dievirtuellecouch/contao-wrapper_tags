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
use Contao\StringUtil;
use Contao\System;

class OpeningTagsElement extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_wt_opening_tags';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    private function isBackendRequest(): bool
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        return $request && $request->attributes->get('_scope') === 'backend';
    }

    public function generate()
    {
        // Contao 5: use StringUtil::deserialize instead of deprecated global function
        $this->wt_opening_tags = StringUtil::deserialize($this->wt_opening_tags, true);

        // Tags data is incorrect
        if (!is_array($this->wt_opening_tags)) {
            $this->wt_opening_tags = [];
        }

        if ($this->isBackendRequest()) {

            $template = new BackendTemplate('be_wildcard_opening_tags');
            $template->wildcard = '### ' . $GLOBALS['TL_LANG']['CTE']['wt_opening_tags'][0] . ' (id:' . $this->id . ') ###';

            $template->tags = $this->wt_opening_tags;
            $ver = \defined('VERSION') ? \constant('VERSION') : '5.3';
            $template->version = version_compare($ver, '3.5', '>') ? 'version-over-35' : 'version-35';

            return $template->parse();
        }

        return parent::generate();
    }

    /**
     * Compile element data.
     */
    protected function compile()
    {
        /** @var array $tags */
        $tags = $this->wt_opening_tags;

        // Compile insert tags in the attribute name
        foreach ($tags as $i => $tag) {
            if ($tag['attributes']) {
                foreach ($tag['attributes'] as $t => $attribute) {
                    $attribute['name'] = \Contao\System::getContainer()->get('contao.insert_tag.parser')->replace($attribute['name']);

                    $tags[$i]['attributes'][$t] = $attribute;
                }
            }
        }

        $this->Template->tags = $tags;
    }
}
