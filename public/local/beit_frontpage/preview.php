<?php
/**
 * BEIT Frontpage — page d'aperçu d'un cours pour les visiteurs.
 *
 * Affiche la présentation du cours (image, résumé, enseignants) avec un
 * bouton « Se connecter pour accéder ». Si l'utilisateur est déjà connecté,
 * il est redirigé directement vers le cours.
 *
 * @package   local_beit_frontpage
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

// Sécurité : ne jamais exposer le cours du site, ni un cours masqué.
if ($course->id == SITEID || !$course->visible) {
    redirect(new moodle_url('/'));
}

$courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);

// Utilisateur déjà connecté -> accès direct au cours.
if (isloggedin() && !isguestuser()) {
    redirect($courseurl);
}

$context = context_course::instance($course->id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/beit_frontpage/preview.php', ['id' => $course->id]));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('frontpage');
$PAGE->requires->css('/local/beit_frontpage/styles.css');

// Données d'affichage.
$courseimage = local_beit_frontpage_course_image($course->id);
$summary = format_text($course->summary, $course->summaryformat ?? FORMAT_HTML);

// Enseignants du cours.
$teachers = [];
$teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($teacherrole) {
    $users = get_role_users($teacherrole->id, $context);
    foreach ($users as $u) {
        $teachers[] = fullname($u);
    }
}

// URL de connexion qui ramène vers le cours après login.
$loginurl = new moodle_url('/login/index.php');

echo $OUTPUT->header();
?>
<div class="beit-preview">
    <a href="<?php echo (new moodle_url('/'))->out(); ?>" class="beit-preview-back">
        &larr; <?php echo get_string('backtocatalog', 'local_beit_frontpage'); ?>
    </a>

    <div class="beit-preview-card">
        <div class="beit-preview-media">
            <?php if ($courseimage): ?>
                <img src="<?php echo $courseimage; ?>" alt="<?php echo s($course->fullname); ?>">
            <?php else: ?>
                <div class="beit-preview-media-placeholder">
                    <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="beit-preview-content">
            <span class="beit-preview-shortname"><?php echo s($course->shortname); ?></span>
            <h1 class="beit-preview-title"><?php echo format_string($course->fullname); ?></h1>

            <?php if (!empty($teachers)): ?>
                <p class="beit-preview-teachers">
                    <i class="fa fa-chalkboard-teacher" aria-hidden="true"></i>
                    <?php echo s(implode(', ', $teachers)); ?>
                </p>
            <?php endif; ?>

            <?php if (trim(strip_tags($summary)) !== ''): ?>
                <div class="beit-preview-summary"><?php echo $summary; ?></div>
            <?php else: ?>
                <p class="beit-preview-summary beit-preview-summary--empty">
                    <?php echo get_string('nosummary', 'local_beit_frontpage'); ?>
                </p>
            <?php endif; ?>

            <div class="beit-preview-cta-box">
                <p class="beit-preview-cta-text">
                    <?php echo get_string('logintoaccess', 'local_beit_frontpage'); ?>
                </p>
                <a href="<?php echo $loginurl->out(); ?>" class="beit-catalog-cta">
                    <?php echo get_string('logintoaccessbtn', 'local_beit_frontpage'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();
