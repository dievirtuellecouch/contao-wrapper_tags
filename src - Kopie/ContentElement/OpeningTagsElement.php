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
    protected $strTemplate = 'ce_wt_opening_tags';

    private function isBackendRequest(): bool
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        return $request && $request->attributes->get('_scope') === 'backend';
    }

    public function generate()
    {
        $this->wt_opening_tags = $this->normalizeTags($this->wt_opening_tags);

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

    protected function compile()
    {
        $tags = $this->normalizeTags($this->wt_opening_tags);

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

    /**
     * Normalize tags structure from DB (serialized, JSON, or legacy forms).
     */
    private function normalizeTags($raw): array
    {
        $tags = StringUtil::deserialize($raw, true);

        if (!is_array($tags) || (isset($tags['tag']) && is_string($tags['tag']))) {
            $decoded = null;
            if (is_string($raw)) {
                $trim = ltrim($raw);
                if ($trim !== '' && ($trim[0] === '[' || $trim[0] === '{')) {
                    $decoded = json_decode($raw, true);
                }
            }
            if (is_array($decoded)) {
                $tags = $decoded;
            } elseif (isset($tags['tag']) && is_string($tags['tag'])) {
                $tags = [$tags];
            }
        }

        if (!is_array($tags)) {
            return [];
        }

        foreach ($tags as $i => $tag) {
            $t = is_array($tag) ? $tag : [];
            $name = isset($t['tag']) ? (string) $t['tag'] : '';
            $class = isset($t['class']) ? (string) $t['class'] : '';
            $attrs = $t['attributes'] ?? [];

            // Support associative attributes name=>value
            if (!is_array($attrs)) {
                $attrs = [];
            } elseif ($this->isAssoc($attrs)) {
                $norm = [];
                foreach ($attrs as $an => $av) {
                    $norm[] = ['name' => (string) $an, 'value' => (string) $av];
                }
                $attrs = $norm;
            }

            // If class is empty but provided as attribute, pick it up
            if ($class === '' && is_array($attrs)) {
                foreach ($attrs as $k => $a) {
                    if (isset($a['name']) && strtolower((string) $a['name']) === 'class') {
                        $class = (string) ($a['value'] ?? '');
                        unset($attrs[$k]);
                        $attrs = array_values($attrs);
                        break;
                    }
                }
            }

            $tags[$i] = [
                'tag' => $name,
                'class' => $class,
                'attributes' => $attrs,
            ];
        }

        return $tags;
    }

    private function isAssoc(array $arr): bool
    {
        foreach (array_keys($arr) as $k) {
            if (!is_int($k)) {
                return true;
            }
        }
        return false;
    }
}
