<?php
/**
 * Theme configuration for BEIT.
 *
 * @package   theme_beit
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/lib.php';

$THEME->name = 'beit';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->editor_scss = ['editor'];
$THEME->usefallback = true;
$THEME->scss = static function($theme) {
    return theme_beit_get_main_scss_content($theme);
};

$drawerlayout = [
    'file' => 'drawers.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
];
$THEME->layouts = [
    'base' => ['file' => 'drawers.php', 'regions' => []],
    'standard' => $drawerlayout,
    'course' => $drawerlayout + ['options' => ['langmenu' => true]],
    'coursecategory' => $drawerlayout,
    'incourse' => $drawerlayout,
    'frontpage' => $drawerlayout + ['options' => ['nonavbar' => true]],
    'admin' => $drawerlayout,
    'mycourses' => $drawerlayout + ['options' => ['nonavbar' => true]],
    'mydashboard' => $drawerlayout + ['options' => ['nonavbar' => true, 'langmenu' => true]],
    'mypublic' => $drawerlayout,
    'login' => ['file' => 'login.php', 'regions' => [], 'options' => ['langmenu' => true]],
    'popup' => ['file' => 'columns1.php', 'regions' => [], 'options' => ['nofooter' => true, 'nonavbar' => true]],
    'frametop' => ['file' => 'columns1.php', 'regions' => [], 'options' => ['nofooter' => true, 'nocoursefooter' => true]],
    'embedded' => ['file' => 'embedded.php', 'regions' => ['side-pre'], 'defaultregion' => 'side-pre'],
    'maintenance' => ['file' => 'maintenance.php', 'regions' => []],
    'print' => ['file' => 'columns1.php', 'regions' => [], 'options' => ['nofooter' => true, 'noactivityheader' => true]],
    'redirect' => ['file' => 'embedded.php', 'regions' => []],
    'report' => $drawerlayout,
    'secure' => $drawerlayout,
];

$THEME->enable_dock = false;
$THEME->extrascsscallback = 'theme_beit_get_extra_scss';
$THEME->prescsscallback = 'theme_beit_get_pre_scss';
$THEME->precompiledcsscallback = 'theme_beit_get_precompiled_css';
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->activityheaderconfig = ['notitle' => true];

