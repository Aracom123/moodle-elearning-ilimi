<?php
/**
 * BEIT Frontpage — page catalogue autonome (liste publique des cours).
 *
 * Utilisable directement : /local/beit_frontpage/index.php
 * La page d'accueil du site utilise frontpage.php via customfrontpageinclude.
 *
 * @package   local_beit_frontpage
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/beit_frontpage/index.php'));
$PAGE->set_title(get_string('catalogtitle', 'local_beit_frontpage'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('frontpage');
$PAGE->requires->css('/local/beit_frontpage/styles.css');

$isloggedin = isloggedin() && !isguestuser();
$courses = local_beit_frontpage_get_courses();

echo $OUTPUT->header();
echo local_beit_frontpage_render_catalog($courses, $isloggedin);
echo $OUTPUT->footer();
