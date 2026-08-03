<?php

declare(strict_types=1);

// Wrapper types
$GLOBALS['TL_WRAPPERS']['start'][] = 'wrapper_tag_start';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'wrapper_tag_stop';
$GLOBALS['TL_WRAPPERS']['single'][] = 'wrapper_tag_complete';

// Defaults
$GLOBALS['TL_CONFIG']['wt_use_colors'] = true;
$GLOBALS['TL_CONFIG']['wt_hide_validation_status'] = false;
$GLOBALS['TL_CONFIG']['wt_allowed_tags']
    = '<div><span><article><aside><section><nav><header><footer><main>'
    . '<ul><ol><li><p><h1><h2><h3><h4><h5><h6>';
