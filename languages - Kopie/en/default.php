<?php

$GLOBALS['TL_LANG']['CTE']['wt_opening_tags'] = array('Opening tags', 'Adds multiple opened html tags to the page.');
$GLOBALS['TL_LANG']['CTE']['wt_closing_tags'] = array('Closing tags', 'Adds multiple closed html tags to the page.');
$GLOBALS['TL_LANG']['CTE']['wt_complete_tags'] = array('Complete tags', 'Adds multiple complete html tags to the page.');
$GLOBALS['TL_LANG']['CTE']['wrapper_tags'] = 'Wrapper tags';

$GLOBALS['TL_LANG']['MSC']['wt.dataCorrupted'] = 'Corrupted data';
$GLOBALS['TL_LANG']['MSC']['wt.statusTitle'] = 'Wrapper tags';
$GLOBALS['TL_LANG']['MSC']['wt.statusOk'] = 'ok';
$GLOBALS['TL_LANG']['MSC']['wt.validationError'] = 'Validation error';
$GLOBALS['TL_LANG']['MSC']['wt.statusClosingNoOpening'] = 'Error: Closing tag "</%s>" (id:%d) is without opening tag.';
$GLOBALS['TL_LANG']['MSC']['wt.statusOpeningNoClosing'] = 'Error: Opening tag "<%s>" (id:%d) is without closing tag.';
$GLOBALS['TL_LANG']['MSC']['wt.statusOpeningWrongPairing'] = 'Error: Opening tag "<%s>" (id:%d) is paired with closing tag "</%s>" (id:%d).';
$GLOBALS['TL_LANG']['MSC']['wt.statusOpeningWrongPairingWithOther'] = 'Error: Opening tag "<%s>" (id:%d) is paired with wrong closing element "%s" (id:%d).';
$GLOBALS['TL_LANG']['MSC']['wt.statusClosingWrongPairingWithOther'] = 'Error: Closing tag "</%s>" (id:%d) is paired with wrong opening element "%s" (id:%d).';
$GLOBALS['TL_LANG']['MSC']['wt.statusClosingWrongPairingNeedSplit'] = 'Error: Closing tags (id:%d) is paired with many smaller opening tags. First one is to big (id:%d).';

$GLOBALS['TL_LANG']['tl_settings']['wt_use_colors'] = ['Wrapper tags colors in BE', 'Colorize wrapper tags indentation in the backend content list.'];
$GLOBALS['TL_LANG']['tl_settings']['wt_hide_validation_status'] = ['Hide validation status', 'Hide wrapper tags validation status in the article header.'];
$GLOBALS['TL_LANG']['tl_settings']['wt_allowed_tags'] = ['Allowed tags', 'List the HTML tags allowed for wrapper tags, e.g. <div><span>…'];

