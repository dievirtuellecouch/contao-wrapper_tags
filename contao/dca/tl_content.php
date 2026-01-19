<?php

use Contao\Input;

$GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'] = ['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'onChildRecordCallback'];
$GLOBALS['TL_DCA']['tl_content']['list']['sorting']['header_callback'] = ['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'onHeaderCallback'];

$GLOBALS['TL_DCA']['tl_content']['palettes']['wt_opening_tags'] = '{type_legend},type;{wt_legend},wt_opening_tags;{template_legend:hide},customTpl;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['wt_closing_tags'] = '{type_legend},type;{wt_legend},wt_closing_tags;{template_legend:hide},customTpl;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['wt_complete_tags'] = '{type_legend},type;{wt_legend},wt_complete_tags;{template_legend:hide},customTpl;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['wt_opening_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_opening_tags'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'save_callback' => [['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'onSaveCallback']],
    'eval' => [
        'mandatory' => true,
        'dragAndDrop' => true,
        'columnFields' => [
            'tag' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_tag'],
                'inputType' => 'select',
                'options_callback' => ['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'getTags'],
            ],
            'attributes' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute'],
                'exclude' => true,
                'inputType' => 'multiColumnWizard',
                'eval' => [
                    'tl_class' => 'attributes',
                    'minCount' => 1,
                    'dragAndDrop' => true,
                    'allowHtml' => false,
                    'columnFields' => [
                        'name' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_name'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => ['allowHtml' => false],
                        ],
                        'value' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_value'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => ['allowHtml' => false],
                        ],
                    ],
                ],
            ],
            'class' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_class'],
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['allowHtml' => false],
            ],
        ],
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['wt_closing_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_closing_tags'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'eval' => [
        'mandatory' => true,
        'columnsCallback' => ['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'onClosingTagsColumnsCallback'],
        'buttons' => ['new' => false],
        'dragAndDrop' => true,
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['wt_complete_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_complete_tags'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'save_callback' => [['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'onSaveCallback']],
    'eval' => [
        'mandatory' => true,
        'dragAndDrop' => true,
        'columnFields' => [
            'tag' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_tag'],
                'inputType' => 'select',
                'options_callback' => ['Zmyslny\\WrapperTags\\EventListener\\ContentListener', 'getTags'],
            ],
            'void' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_void'],
                'exclude' => true,
                'inputType' => 'checkbox',
            ],
            'attributes' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute'],
                'exclude' => true,
                'inputType' => 'multiColumnWizard',
                'eval' => [
                    'tl_class' => 'attributes',
                    'minCount' => 1,
                    'dragAndDrop' => true,
                    'allowHtml' => false,
                    'columnFields' => [
                        'name' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_name'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => ['allowHtml' => false],
                        ],
                        'value' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_value'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => ['allowHtml' => false],
                        ],
                    ],
                ],
            ],
            'class' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_class'],
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['allowHtml' => false],
            ],
        ],
    ],
    'sql' => 'blob NULL',
];

