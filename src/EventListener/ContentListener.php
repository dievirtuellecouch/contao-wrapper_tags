<?php

namespace Zmyslny\WrapperTags\EventListener;

use Contao\DataContainer;
use ReflectionClass;
use Zmyslny\WrapperTags\Util\TagNormalizer;

class ContentListener extends \tl_content
{
    public function onSaveCallback($data, DataContainer $dc)
    {
        $tags = TagNormalizer::normalize($data, ($dc->field ?? '') === 'wt_complete_tags');

        foreach ($tags as &$tag) {
            $attributes = array();
            $names = array();

            foreach ($tag['attributes'] as $attribute) {
                if ('' !== $attribute['name']) {
                    if (isset($names[$attribute['name']])) {
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['MSC']['wt.errorAttributeNameAlreadyUsed'], $attribute['name']));
                    }

                    $names[$attribute['name']] = true;

                    if (!preg_match('/^[A-Za-z]+[\w\-\:\.]*(\{{2}[\w\:]+\}{2}[\w\-\:\.]*){0,10}$/', $attribute['name'])) {
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['MSC']['wt.errorAttributeName'], $attribute['name']));
                    }

                    if ('' === $attribute['value']) {
                        throw new \Exception(sprintf($GLOBALS['TL_LANG']['MSC']['wt.errorAttributeNameWithoutValue'], $attribute['name']));
                    }
                } elseif ('' !== $attribute['value']) {
                    throw new \Exception(sprintf($GLOBALS['TL_LANG']['MSC']['wt.errorAttributeValueWithoutName'], $attribute['value']));
                }

                if ('' !== $attribute['value'] && '' !== $attribute['name']) {
                    $attributes[] = $attribute;
                }
            }

            $tag['attributes'] = $attributes;
        }

        return serialize($tags);
    }

    public function onChildRecordCallback($row)
    {
        if (isset($GLOBALS['WrapperTags']['indents']) && is_array($GLOBALS['WrapperTags']['indents'])) {
            $indent = $GLOBALS['WrapperTags']['indents'][$row['id']] ?? null;
            if (null !== $indent) {
                $this->setChildRecordClass($indent);
            }
        }
        return parent::addCteType($row);
    }

    protected function setChildRecordClass($indent)
    {
        $wrapperTagClass = $indent['type'] === 'wt_opening_tags' || $indent['type'] === 'wt_closing_tags' ? 'wrapper-tag' : '';
        $middleClass = (isset($indent['middle'])) ? ' indent-tags-closing-middle' : '';
        $colorizeClass = $indent['colorize-class'] ?? '';
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_class'] = $indent['value'] > 0 ? 'clear-indent ' . $wrapperTagClass . ' indent indent_' . $indent['value'] . $middleClass . ' ' . $colorizeClass : 'clear-indent ' . $wrapperTagClass . ' indent_0 ' . $middleClass;
    }

    public function onClosingTagsColumnsCallback()
    {
        return array(
            'tag' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_tag'],
                'inputType' => 'select',
                'options_callback' => array('Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'getTags'),
            )
        );
    }

    public function getTags()
    {
        $allowed = \Contao\Config::get('wt_allowed_tags');
        if (!\is_string($allowed) || trim($allowed) === '') {
            $allowed = $GLOBALS['TL_CONFIG']['wt_allowed_tags']
                ?? '<div><span><article><aside><section><nav><header><footer><main>'
                 . '<ul><ol><li><p><h1><h2><h3><h4><h5><h6>';
        }
        $parts = \Contao\StringUtil::trimsplit('><', $allowed);
        if (empty($parts)) {
            return array('div');
        }
        $parts[0] = str_replace('<', '', $parts[0]);
        $parts[count($parts) - 1] = str_replace('>', '', $parts[count($parts) - 1]);
        $tags = array();
        foreach ($parts as $p) {
            $t = strtolower(trim($p));
            if ($t !== '' && !in_array($t, $tags, true)) {
                $tags[] = $t;
            }
        }
        foreach (array('div', 'span') as $ensure) {
            if (!in_array($ensure, $tags, true)) {
                $tags[] = $ensure;
            }
        }
        return $tags;
    }

    public function onHeaderCallback($add, DataContainer $dc)
    {
        // Gracefully skip if DB columns are not yet added (pre-migration)
        if (!\is_object($this->Database) 
            || !$this->Database->fieldExists('wt_opening_tags', 'tl_content') 
            || !$this->Database->fieldExists('wt_closing_tags', 'tl_content')) {
            return $add;
        }

        $result = $this->Database
            ->prepare('SELECT id FROM `tl_content` WHERE pid = ? AND ptable = ? AND invisible != ? AND type IN (\'wt_opening_tags\',\'wt_closing_tags\')')
            ->execute($dc->currentPid, $dc->parentTable, '1');

        if ($result->numRows === 0) {
            return $add;
        }

        $query = 'SELECT id, type, wt_opening_tags, wt_closing_tags, invisible FROM `tl_content` WHERE pid = ? AND ptable = ? ORDER BY sorting ASC';

        $stmt = $this->Database->prepare($query);
        $result = $stmt->execute($dc->currentPid, $dc->parentTable);

        $langMSC = &$GLOBALS['TL_LANG']['MSC'];
        $langMSC['wt.statusTitle'] = $langMSC['wt.statusTitle'] ?? 'Wrapper tags';
        $langMSC['wt.validationError'] = $langMSC['wt.validationError'] ?? 'Validation error';
        $langMSC['wt.statusOk'] = $langMSC['wt.statusOk'] ?? 'OK';
        $langMSC['wt.statusOpeningNoClosing'] = $langMSC['wt.statusOpeningNoClosing'] ?? 'Opening tag %s (ID %s) not closed';
        $langMSC['wt.dataCorrupted'] = $langMSC['wt.dataCorrupted'] ?? 'Data corrupted';
        $langMSC['wt.statusOpeningWrongPairingWithOther'] = $langMSC['wt.statusOpeningWrongPairingWithOther'] ?? 'Opening %s (ID %s) wrong pairing with %s (ID %s)';
        $langMSC['wt.statusClosingNoOpening'] = $langMSC['wt.statusClosingNoOpening'] ?? 'Closing %s (ID %s) without opening';
        $langMSC['wt.statusClosingWrongPairingWithOther'] = $langMSC['wt.statusClosingWrongPairingWithOther'] ?? 'Closing %s (ID %s) wrong pairing with %s (ID %s)';
        $langMSC['wt.statusOpeningWrongPairing'] = $langMSC['wt.statusOpeningWrongPairing'] ?? 'Opening %s (ID %s) wrong pairing with closing %s (ID %s)';
        $langMSC['wt.statusClosingWrongPairingNeedSplit'] = $langMSC['wt.statusClosingWrongPairingNeedSplit'] ?? 'Closing element %s needs split with opening %s';

        $statusTitle = $langMSC['wt.statusTitle'];
        $status = array();

        if ($result->numRows === 0) {
            $status[$statusTitle] = '<span class="tl_red">' . $GLOBALS['TL_LANG']['MSC']['wt.validationError'] . '</span>';
            return $add + $status;
        }

        $indentLevel = 0;
        $openStack = array();
        $GLOBALS['WrapperTags']['indents'] = array();
        $status = array();
        $hasError = false;
        $hideStatus = \Contao\Config::get('wt_hide_validation_status');
        if ($hideStatus) {
            $hasError = true;
        }

        foreach ($result->fetchAllAssoc() as $cte) {
            $isWrapperStart = in_array($cte['type'], $GLOBALS['TL_WRAPPERS']['start'] ?? array(), true);
            $isWrapperStop = in_array($cte['type'], $GLOBALS['TL_WRAPPERS']['stop'] ?? array(), true);
            $isVisible = $cte['invisible'] !== '1';
            if ($isWrapperStart) {
                $this->wrapperStart($cte, $isVisible, $statusTitle, $openStack, $indentLevel, $hasError, $status);
            } elseif ($isWrapperStop) {
                $this->wrapperStop($cte, $isVisible, $statusTitle, $openStack, $indentLevel, $hasError, $status);
            } else {
                $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => $indentLevel);
            }
        }

        if (!$hasError && count($openStack)) {
            for ($i = count($openStack) - 1; $i >= 0; --$i) {
                if ($openStack[$i]['type'] === 'wt_opening_tags') {
                    $status[$statusTitle] = '<span class="tl_red">' . sprintf($GLOBALS['TL_LANG']['MSC']['wt.statusOpeningNoClosing'], $openStack[$i]['tag'], $openStack[$i]['id']) . '</span>';
                    $hasError = true;
                    break;
                }
            }
        }

        if (!$hasError) {
            $status[$statusTitle] = $GLOBALS['TL_LANG']['MSC']['wt.statusOk'];
        }
        if ($hideStatus) {
            $status = array();
        }

        $useColors = \Contao\Config::get('wt_use_colors');

        if (class_exists('ReflectionClass')) {
            $reflectionClass = new ReflectionClass(get_class($dc));
            $reflectionProperty = $reflectionClass->getProperty('limit');
            $reflectionProperty->setAccessible(true);
            $limit = $reflectionProperty->getValue($dc);
            if (strlen($limit)) {
                $limit = explode(',', $limit);
                $offset = (int)$limit[0];
                if ($offset > 0) {
                    $index = 1;
                    $firstElementOnPage = $offset + 1;
                    foreach ($GLOBALS['WrapperTags']['indents'] as $indent) {
                        if ($index === $firstElementOnPage) {
                            $this->setChildRecordClass($indent + array('colorize-class' => ($useColors ? 'colorize-wrapper-tags' : '')));
                            break;
                        }
                        ++$index;
                    }
                }
            } else {
                if ($GLOBALS['WrapperTags']['indents'] !== array()) {
                    $this->setChildRecordClass($GLOBALS['WrapperTags']['indents'][key($GLOBALS['WrapperTags']['indents'])]);
                }
            }
        }

        if ($GLOBALS['WrapperTags']['indents'] !== array()) {
            end($GLOBALS['WrapperTags']['indents']);
            $lastKey = key($GLOBALS['WrapperTags']['indents']);
            $lastIndent = $GLOBALS['WrapperTags']['indents'][$lastKey];
            $reversed = array_reverse($GLOBALS['WrapperTags']['indents'], true);

            foreach ($reversed as $id => &$indent) {
                $nowIndent = $indent;
                $indent = $lastIndent + array('colorize-class' => ($useColors ? 'colorize-wrapper-tags' : ''));
                $lastIndent = $nowIndent;
            }

            $GLOBALS['WrapperTags']['indents'] = array_reverse($reversed, true);
        }

        return $add + $status;
    }

    protected function wrapperStart($cte, $isVisible, $statusTitle, &$openStack, &$indentLevel, &$hasError, &$status)
    {
        if ('wt_opening_tags' !== $cte['type']) {
            if ($isVisible) {
                $openStack[] = array(
                    'id' => $cte['id'],
                    'type' => $cte['type']
                );
                $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => $indentLevel);
                ++$indentLevel;
            } else {
                $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => $indentLevel);
            }
        } else {
            if ($isVisible) {
                $startTags = TagNormalizer::normalize($cte['wt_opening_tags']);
                if (!$hasError && $startTags === array()) {
                    $status[$statusTitle] = '<span class="tl_red">' . $GLOBALS['TL_LANG']['MSC']['wt.dataCorrupted'] . '</span>';
                    $hasError = true;
                }

                foreach ($startTags as $tag) {
                    $openStack[] = array(
                        'id' => $cte['id'],
                        'type' => 'wt_opening_tags',
                        'tag' => $tag['tag']
                    );
                }

                $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => $indentLevel);
                $indentLevel += count($startTags);
            } else {
                $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => $indentLevel);
            }
        }
    }

    protected function wrapperStop($cte, $isVisible, $statusTitle, &$openStack, &$indentLevel, &$hasError, &$status)
    {
        if ('wt_closing_tags' !== $cte['type']) {
            if ($isVisible) {
                $openingTags = end($openStack);

                if (!$hasError && is_array($openingTags) && $openingTags['type'] === 'wt_opening_tags') {
                    $status[$statusTitle] = '<span class="tl_red">' . sprintf($GLOBALS['TL_LANG']['MSC']['wt.statusOpeningWrongPairingWithOther'], $openingTags['tag'], $openingTags['id'], $GLOBALS['TL_LANG']['CTE'][$cte['type']][0], $cte['id']) . '</span>';
                    $hasError = true;
                }

                if ($openingTags !== false) {
                    array_pop($openStack);
                    $indentLevel = max(0, $indentLevel - 1);
                }
            }
            $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => $indentLevel);
        } else {
            $GLOBALS['WrapperTags']['indents'][$cte['id']] = array('type' => $cte['type'], 'value' => ($indentLevel > 1 ? $indentLevel - 1 : 0));

            if (!$isVisible) {
                $GLOBALS['WrapperTags']['indents'][$cte['id']]['value'] += $indentLevel > 0 ? 1 : 0;
            } else {
                $closingTags = TagNormalizer::normalize($cte['wt_closing_tags']);

                if (!$hasError && $closingTags === array()) {
                    $status[$statusTitle] = '<span class="tl_red">' . $GLOBALS['TL_LANG']['MSC']['wt.dataCorrupted'] . '</span>';
                    $hasError = true;
                }

                foreach ($closingTags as $closingTag) {
                    $openingTags = end($openStack);

                    if ($openingTags === false) {
                        if (!$hasError) {
                            $status[$statusTitle] = '<span class="tl_red">' . sprintf($GLOBALS['TL_LANG']['MSC']['wt.statusClosingNoOpening'], $closingTag['tag'], $cte['id']) . '</span>';
                            $hasError = true;
                        }

                        break;
                    }

                    if ($openingTags['type'] !== 'wt_opening_tags') {
                        if (!$hasError) {
                            $openingLabel = $GLOBALS['TL_LANG']['CTE'][$openingTags['type']][0] ?? $openingTags['type'];
                            $status[$statusTitle] = '<span class="tl_red">' . sprintf($GLOBALS['TL_LANG']['MSC']['wt.statusClosingWrongPairingWithOther'], $closingTag['tag'], $cte['id'], $openingLabel, $openingTags['id']) . '</span>';
                            $hasError = true;
                        }

                        break;
                    }

                    if ($openingTags['tag'] !== $closingTag['tag']) {
                        if (!$hasError) {
                            $status[$statusTitle] = '<span class="tl_red">' . sprintf($GLOBALS['TL_LANG']['MSC']['wt.statusOpeningWrongPairing'], $openingTags['tag'], $openingTags['id'], $closingTag['tag'], $cte['id']) . '</span>';
                            $hasError = true;
                        }

                        break;
                    }

                    array_pop($openStack);
                    $indentLevel = max(0, $indentLevel - 1);
                }
            }
        }
    }
}
