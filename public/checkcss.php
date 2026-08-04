<?php
require_once('../config.php');
require_login(); if (!is_siteadmin()) die('no');
header('Content-Type: text/plain; charset=utf-8');
$theme = theme_config::load('beit');
$css = $theme->get_css_content();
foreach (['login-form-forgotpassword','display: contents','loginform > .d-flex','login-signup'] as $n) {
    echo (strpos($css, $n) !== false ? "TROUVE   " : "ABSENT   ") . $n . "\n";
}
echo "\nTaille CSS : " . strlen($css) . " octets\n";
