<?php
// Smoke-test the front page with an administrator in editing mode.

define('CLI_SCRIPT', true);

require_once('/var/www/moodle/config.php');

$admin = get_admin();
\core\session\manager::set_user($admin);
$USER->editing = 1;

$_GET = [];
$_POST = [];
$_REQUEST = [];

chdir($CFG->dirroot);
ob_start();
require($CFG->dirroot . '/index.php');
$html = ob_get_clean();

if (strlen($html) < 1000 || !str_contains($html, 'course-content')) {
    fwrite(STDERR, "Homepage content was not rendered as expected.\n");
    exit(1);
}

if (str_contains($html, 'has_any_capability(): Argument #2')) {
    fwrite(STDERR, "Homepage rendered the null context capability error.\n");
    exit(1);
}

fwrite(STDOUT, "Homepage rendered successfully for an administrator in editing mode.\n");
