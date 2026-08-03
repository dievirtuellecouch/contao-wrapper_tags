<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['CTE']['wrapper_tag_start'] = ['Öffnende Tags', 'Fügt mehrere öffnende HTML-Tags auf der Seite ein.'];
$GLOBALS['TL_LANG']['CTE']['wrapper_tag_stop'] = ['Schließende Tags', 'Fügt mehrere schließende HTML-Tags auf der Seite ein.'];
$GLOBALS['TL_LANG']['CTE']['wrapper_tag_complete'] = ['Komplette Tags', 'Fügt mehrere komplette HTML-Tags auf der Seite ein.'];

// Legacy labels are kept so records are readable before the type migration is run.
$GLOBALS['TL_LANG']['CTE']['wt_opening_tags'] = array('Öffnendes Tag', 'Fügt ein öffnendes HTML-Tag auf der Seite ein.');
$GLOBALS['TL_LANG']['CTE']['wt_closing_tags'] = array('Schließende Tags', 'Fügt mehrere schließende HTML-Tags auf der Seite ein.');
$GLOBALS['TL_LANG']['CTE']['wt_complete_tags'] = array('Komplette Tags', 'Fügt mehrere komplette HTML-Tags auf der Seite ein.');
$GLOBALS['TL_LANG']['CTE']['wrapper_tags'] = 'Wrapper-Tags';

$GLOBALS['TL_LANG']['MSC']['wt.dataCorrupted'] = 'Daten beschädigt';
$GLOBALS['TL_LANG']['MSC']['wt.statusTitle'] = 'Wrapper-Tags';
$GLOBALS['TL_LANG']['MSC']['wt.statusOk'] = 'OK';
$GLOBALS['TL_LANG']['MSC']['wt.validationError'] = 'Validierungsfehler';
$GLOBALS['TL_LANG']['MSC']['wt.statusClosingNoOpening'] = 'Fehler: Schließender Tag "</%s>" (ID:%d) ohne öffnenden Tag.';
$GLOBALS['TL_LANG']['MSC']['wt.statusOpeningNoClosing'] = 'Fehler: Öffnender Tag "<%s>" (ID:%d) ohne schließenden Tag.';
$GLOBALS['TL_LANG']['MSC']['wt.statusOpeningWrongPairing'] = 'Fehler: Öffnender Tag "<%s>" (ID:%d) ist mit schließendem Tag "</%s>" (ID:%d) falsch gepaart.';
$GLOBALS['TL_LANG']['MSC']['wt.statusOpeningWrongPairingWithOther'] = 'Fehler: Öffnender Tag "<%s>" (ID:%d) ist mit falschem Element "%s" (ID:%d) gepaart.';
$GLOBALS['TL_LANG']['MSC']['wt.statusClosingWrongPairingWithOther'] = 'Fehler: Schließender Tag "</%s>" (ID:%d) ist mit falschem öffnenden Element "%s" (ID:%d) gepaart.';
$GLOBALS['TL_LANG']['MSC']['wt.statusClosingWrongPairingNeedSplit'] = 'Fehler: Schließender Tag (ID:%d) ist mit mehreren kleineren Öffnungs-Tags gepaart. Der erste ist zu groß (ID:%d).';

$GLOBALS['TL_LANG']['MSC']['wt.errorTagName'] = 'Der HTML-Tag-Name „%s“ ist ungültig.';
$GLOBALS['TL_LANG']['MSC']['wt.errorAttributeNameAlreadyUsed'] = 'Das Attribut „%s“ wurde mehr als einmal verwendet.';
$GLOBALS['TL_LANG']['MSC']['wt.errorAttributeNameWithoutValue'] = 'Für das Attribut „%s“ wurde kein Wert eingegeben.';
$GLOBALS['TL_LANG']['MSC']['wt.errorAttributeValueWithoutName'] = 'Für den Attributwert „%s“ wurde kein Name eingegeben.';
$GLOBALS['TL_LANG']['MSC']['wt.errorAttributeName'] = 'Der Attributname „%s“ ist ungültig.';

$GLOBALS['TL_LANG']['tl_settings']['wt_use_colors'] = ['Wrapper-Tags einfärben (BE)', 'Einrückungen der Wrapper-Tags in der Listenansicht farblich kennzeichnen.'];
$GLOBALS['TL_LANG']['tl_settings']['wt_hide_validation_status'] = ['Validierungsstatus ausblenden', 'Validierungsstatus im Artikelkopf ausblenden.'];
$GLOBALS['TL_LANG']['tl_settings']['wt_allowed_tags'] = ['Erlaubte Tags', 'Liste der erlaubten HTML-Tags für Wrapper-Tags, z. B. <div><span>…'];
