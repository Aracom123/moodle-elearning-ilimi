<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype = getenv('MOODLE_DB_TYPE') ?: 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost = getenv('MOODLE_DB_HOST') ?: 'db';
$CFG->dbname = getenv('MOODLE_DB_NAME') ?: 'elearning_db';
$CFG->dbuser = getenv('MOODLE_DB_USER') ?: 'moodle';
$CFG->dbpass = getenv('MOODLE_DB_PASSWORD') ?: 'moodle';
$CFG->prefix = getenv('MOODLE_DB_PREFIX') ?: 'el_';
$CFG->dboptions = [
    'dbpersist' => false,
    'dbport' => getenv('MOODLE_DB_PORT') ?: '',
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
];

$CFG->wwwroot = getenv('MOODLE_WWWROOT') ?: 'http://localhost:8080';
$CFG->sslproxy = filter_var(getenv('MOODLE_SSLPROXY') ?: 'false', FILTER_VALIDATE_BOOLEAN);
$CFG->dataroot = '/var/www/moodledata';
$CFG->admin = 'admin';
$CFG->directorypermissions = 02777;
$CFG->routerconfigured = true;
$CFG->customfrontpageinclude = __DIR__ . '/../public/local/beit_frontpage/frontpage.php';

require_once(__DIR__ . '/lib/setup.php');
