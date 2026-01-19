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

class ClosingTagsElement extends ContentElement
{
    protected $strTemplate = 'ce_wt_closing_tags';

    private function isBackendRequest(): bool
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        return $request && $request->attributes->get('_scope') === 'backend';
    }

    public function generate()
    {
        $this->wt_closing_tags = StringUtil::deserialize($this->wt_closing_tags, true);

        if (!is_array($this->wt_closing_tags)) {
            $this->wt_closing_tags = [];
        }

        if ($this->isBackendRequest()) {
            $template = new BackendTemplate('be_wildcard_closing_tags');
            $template->wildcard = '### ' . $GLOBALS['TL_LANG']['CTE']['wt_closing_tags'][0] . ' (id:' . $this->id . ') ###';
            $template->tags = $this->wt_closing_tags;
            $ver = \defined('VERSION') ? \constant('VERSION') : '5.3';
            $template->version = version_compare($ver, '3.5', '>') ? 'version-over-35' : 'version-35';
            return $template->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $this->Template->tags = $this->wt_closing_tags;
    }
}

