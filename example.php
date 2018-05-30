<?php

require_once 'src/i18n.php';

use Philipp15b\i18n;

$i18n = new i18n('lang/lang_{LANGUAGE}.ini', 'langcache/', 'en');
// Parameters: language file path, cache dir, default language (all optional)

// init object: load language files, parse them if not cached, and so on.
$translator = $i18n->init();
?>

<!-- get applied language -->
<p>Applied Language: <?= $i18n->getAppliedLang(); ?> </p>

<!-- get the cache path -->
<p>Cache path: <?= $i18n->getCachePath(); ?></p>

<!-- Get some greetings -->
<p>A greeting: <?= $translator->t('greeting'); ?></p>
<p>Something other: <?= $translator->t('category_somethingother'); ?></p><!-- normally sections in the ini are seperated with an underscore like here. -->
