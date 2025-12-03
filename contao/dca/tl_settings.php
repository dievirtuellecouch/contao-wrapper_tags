<?php

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{wrapper_tags_legend},wt_use_colors,wt_hide_validation_status,wt_allowed_tags';

$GLOBALS['TL_DCA']['tl_settings']['fields']['wt_use_colors'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['wt_use_colors'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['wt_hide_validation_status'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['wt_hide_validation_status'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['wt_allowed_tags'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['wt_allowed_tags'],
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'allowHtml' => true],
];

