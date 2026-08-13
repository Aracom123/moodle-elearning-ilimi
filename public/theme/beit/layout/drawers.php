<?php
/**
 * Drawers layout for the BEIT theme.
 * Same as Boost but renders theme_beit/drawers (with custom footer).
 *
 * @package   theme_beit
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;
$isfrontpage = $PAGE->pagetype === 'site-index';
$islearnerloggedin = isloggedin() && !isguestuser();
$primaryactionurl = $islearnerloggedin
    ? new moodle_url('/my/courses.php')
    : new moodle_url('/login/index.php');
$primaryactionlabel = $islearnerloggedin ? 'Continuer mes cours' : 'Se connecter';
$coursecount = $isfrontpage ? $DB->count_records_select('course', 'id <> :siteid AND visible = 1', ['siteid' => SITEID]) : 0;

$templatecontext = [
    'sitename'               => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'coursefullname'         => $coursefullname,
    'courseurl'              => $courseurl ? $courseurl->out(false) : null,
    'output'                 => $OUTPUT,
    'sidepreblocks'          => $blockshtml,
    'hasblocks'              => $hasblocks,
    'bodyattributes'         => $bodyattributes,
    'courseindexopen'        => $courseindexopen,
    'blockdraweropen'        => $blockdraweropen,
    'courseindex'            => $courseindex,
    'primarymoremenu'        => $primarymenu['moremenu'],
    'secondarymoremenu'      => $secondarynavigation ?: false,
    'mobileprimarynav'       => $primarymenu['mobileprimarynav'],
    'usermenu'               => $primarymenu['user'],
    'langmenu'               => $primarymenu['lang'],
    'forceblockdraweropen'   => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow'               => $overflow,
    'headercontent'          => $headercontent,
    'addblockbutton'         => $addblockbutton,
    'isfrontpage'           => $isfrontpage,
    'learnerloggedin'       => $islearnerloggedin,
    'primaryactionurl'      => $primaryactionurl->out(false),
    'primaryactionlabel'    => $primaryactionlabel,
    'catalogurl'            => (new moodle_url('/course/'))->out(false),
    'homeurl'               => (new moodle_url('/'))->out(false),
    'forgotpasswordurl'     => (new moodle_url('/login/forgot_password.php'))->out(false),
    'announcementsurl'      => (new moodle_url('/mod/forum/view.php', ['f' => forum_get_course_forum(SITEID, 'news')->id]))->out(false),
    'coursecount'           => $coursecount,
];

echo $OUTPUT->render_from_template('theme_beit/drawers', $templatecontext);
