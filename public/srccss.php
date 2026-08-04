<?php
require_once('../config.php');
require_login(); if (!is_siteadmin()) die('no');
require_once($CFG->dirroot.'/theme/beit/lib.php');
header('Content-Type: text/plain; charset=utf-8');
$theme = theme_config::load('beit');
$src = theme_beit_get_extra_scss($theme);
foreach (['display: contents','loginform > .d-flex','min-width: 992px'] as $n) {
    echo (strpos($src, $n) !== false ? "DANS SOURCE   " : "PAS DANS SRC  ") . $n . "\n";
}
echo "\nLongueur source extra scss: " . strlen($src) . "\n";
