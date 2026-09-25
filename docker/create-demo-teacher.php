<?php
// Idempotent test account for the teacher dashboard; never commit credentials.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/user/lib.php';

global $DB;

\core\session\manager::set_user(get_admin());

$username = 'enseignant.demo.isp';
$course = $DB->get_record('course', ['shortname' => 'IA-SANTE-PUBLIQUE-TEST'], '*', MUST_EXIST);
$role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
$teacher = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);

if (!$teacher) {
    $password = getenv('MOODLE_TEACHER_DEMO_PASSWORD') ?: 'A!' . bin2hex(random_bytes(12));
    $userid = user_create_user((object) [
        'username' => $username,
        'password' => $password,
        'firstname' => 'Enseignant',
        'lastname' => 'Démonstration',
        'email' => 'enseignant.demo@example.test',
        'auth' => 'manual',
        'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
        'lang' => 'fr',
    ]);
    $teacher = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    echo "Compte créé : {$username}\nMot de passe initial : {$password}\nConservez-le hors du dépôt Git.\n";
} else {
    echo "Compte existant : {$username} (mot de passe inchangé).\n";
}

$manual = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
$manualplugin = enrol_get_plugin('manual');
if (!$DB->record_exists('user_enrolments', ['enrolid' => $manual->id, 'userid' => $teacher->id])) {
    $manualplugin->enrol_user($manual, $teacher->id, $role->id);
}
echo "Enseignant inscrit au cours de démonstration « {$course->fullname} ».\n";
