<?php
/**
 * Contenu personnalisé de la page d'accueil BEIT.
 * Inclus via $CFG->customfrontpageinclude dans index.php.
 * Les variables $OUTPUT, $DB, $CFG, $USER, $SITE, $PAGE sont disponibles.
 *
 * Note : ce fichier est inclus APRÈS l'impression du <head> par le coeur,
 * donc on ne peut pas utiliser $PAGE->requires->css(). Le CSS est injecté
 * en ligne via local_beit_frontpage_render_catalog().
 *
 * @package   local_beit_frontpage
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$isloggedin = isloggedin() && !isguestuser();
$courses = local_beit_frontpage_get_courses();

echo local_beit_frontpage_render_catalog($courses, $isloggedin);
