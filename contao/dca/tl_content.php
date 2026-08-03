<?php

declare(strict_types=1);

use Zmyslny\WrapperTags\EventListener\ContentListener;

$GLOBALS['TL_DCA']['tl_content']['palettes']['wrapper_tag_start'] = '{type_legend},type;{wt_legend},wt_opening_tags;{template_legend:hide},customTpl;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['wrapper_tag_stop'] = '{type_legend},type;{wt_legend},wt_closing_tags;{template_legend:hide},customTpl;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['wrapper_tag_complete'] = '{type_legend},type;{wt_legend},wt_complete_tags;{template_legend:hide},customTpl;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['wt_opening_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_opening_tags'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'eval' => [
        'mandatory' => true,
        'minCount' => 1,
        'maxCount' => 1,
        'buttons' => [
            'new' => false,
            'delete' => false,
            'move' => false,
        ],
        'columnFields' => [
            'tag' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_tag'],
                'inputType' => 'select',
                'options_callback' => [ContentListener::class, 'getTags'],
                'eval' => [
                    'hideHead' => true,
                    'style' => 'width:170px',
                ],
            ],
            'attributes' => [
                'label' => ['', ''],
                'exclude' => true,
                'inputType' => 'multiColumnWizard',
                'eval' => [
                    'tl_class' => 'attributes',
                    'hideHead' => true,
                    'style' => 'width:520px',
                    'minCount' => 1,
                    'dragAndDrop' => true,
                    'allowHtml' => false,
                    'columnFields' => [
                        'name' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_name'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => [
                                'allowHtml' => false,
                                'hideHead' => true,
                                'style' => 'width:220px',
                            ],
                        ],
                        'value' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_value'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => [
                                'allowHtml' => false,
                                'hideHead' => true,
                                'style' => 'width:220px',
                            ],
                        ],
                    ],
                ],
            ],
            'class' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_class'],
                'exclude' => true,
                'inputType' => 'text',
                'eval' => [
                    'allowHtml' => false,
                    'hideHead' => true,
                    'style' => 'width:200px',
                ],
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
        'columnsCallback' => [ContentListener::class, 'onClosingTagsColumnsCallback'],
        'buttons' => ['new' => false],
        'dragAndDrop' => true,
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['wt_complete_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_complete_tags'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'eval' => [
        'mandatory' => true,
        'dragAndDrop' => true,
        'columnFields' => [
            'tag' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_tag'],
                'inputType' => 'select',
                'options_callback' => [ContentListener::class, 'getTags'],
                'eval' => [
                    'hideHead' => true,
                    'style' => 'width:170px',
                ],
            ],
            'void' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_void'],
                'exclude' => true,
                'inputType' => 'checkbox',
                'eval' => [
                    'hideHead' => true,
                    'style' => 'width:80px',
                ],
            ],
            'attributes' => [
                'label' => ['', ''],
                'exclude' => true,
                'inputType' => 'multiColumnWizard',
                'eval' => [
                    'tl_class' => 'attributes',
                    'hideHead' => true,
                    'style' => 'width:460px',
                    'minCount' => 1,
                    'dragAndDrop' => true,
                    'allowHtml' => false,
                    'columnFields' => [
                        'name' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_name'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => [
                                'allowHtml' => false,
                                'hideHead' => true,
                                'style' => 'width:200px',
                            ],
                        ],
                        'value' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_attribute_value'],
                            'inputType' => 'text',
                            'exclude' => true,
                            'eval' => [
                                'allowHtml' => false,
                                'hideHead' => true,
                                'style' => 'width:200px',
                            ],
                        ],
                    ],
                ],
            ],
            'class' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_class'],
                'exclude' => true,
                'inputType' => 'text',
                'eval' => [
                    'allowHtml' => false,
                    'hideHead' => true,
                    'style' => 'width:180px',
                ],
            ],
        ],
    ],
    'sql' => 'blob NULL',
];
