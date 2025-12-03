<?php

// Register content elements
$GLOBALS['TL_CTE']['wrapper_tags']['wt_opening_tags'] = Zmyslny\WrapperTags\ContentElement\OpeningTagsElement::class;
$GLOBALS['TL_CTE']['wrapper_tags']['wt_closing_tags'] = Zmyslny\WrapperTags\ContentElement\ClosingTagsElement::class;
$GLOBALS['TL_CTE']['wrapper_tags']['wt_complete_tags'] = Zmyslny\WrapperTags\ContentElement\CompleteTagsElement::class;

// Wrapper types
$GLOBALS['TL_WRAPPERS']['start'][] = 'wt_opening_tags';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'wt_closing_tags';
$GLOBALS['TL_WRAPPERS']['single'][] = 'wt_complete_tags';

// Defaults
$GLOBALS['TL_CONFIG']['wt_use_colors'] = true;
$GLOBALS['TL_CONFIG']['wt_hide_validation_status'] = false;
$GLOBALS['TL_CONFIG']['wt_allowed_tags']
    = '<div><span><article><aside>'
    . '<ul><ol><li>';

// Backend assets (Contao 5 bundles path)
if (defined('TL_MODE') && TL_MODE === 'BE') {
    $min = $GLOBALS['TL_CONFIG']['debugMode'] ? '' : '.min';

    if ('flexible' === ($GLOBALS['TL_CONFIG']['backendTheme'] ?? null)) {
        $GLOBALS['TL_CSS']['wt_css'] = '/bundles/zmyslnywrappertags/wrapper-tags-flexible-c44' . $min . '.css';
    } else {
        $GLOBALS['TL_CSS']['wt_css'] = '/bundles/zmyslnywrappertags/wrapper-tags-default-c35' . $min . '.css';
    }

    $GLOBALS['TL_JAVASCRIPT']['wt_js'] = 'bundles/zmyslnywrappertags/wrapper-tags' . $min . '.js';
}

