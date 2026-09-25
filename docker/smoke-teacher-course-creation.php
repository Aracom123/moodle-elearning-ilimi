<?php
// Test automatic granting and revocation on the disposable IA learner account.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

\core\session\manager::set_user(get_admin());

$learner = $DB->get_record('user', ['username' => 'student.ia.test'], '*', MUST_EXIST);
$course = $DB->get_record('course', ['shortname' => 'IA-SANTE-PUBLIQUE-TEST'], '*', MUST_EXIST);
$role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
$categorycontext = context_coursecat::instance($course->category);

if ($DB->record_exists('role_assignments', [
    'roleid' => $role->id, 'userid' => $learner->id, 'contextid' => $coursecontext->id,
])) {
    throw new RuntimeException('Test account already teaches the IA course; refusing to change its assignment.');
}
if (has_capability('moodle/course:create', $categorycontext, $learner)) {
    throw new RuntimeException('Test account can already create courses; the test needs a restricted learner.');
}

try {
    role_assign($role->id, $learner->id, $coursecontext->id);
    if (!has_capability('moodle/course:create', $categorycontext, $learner)) {
        throw new RuntimeException('Teacher assignment did not grant category-level course creation.');
    }
} finally {
    role_unassign($role->id, $learner->id, $coursecontext->id);
}

if (has_capability('moodle/course:create', $categorycontext, $learner)) {
    throw new RuntimeException('Removing the teacher assignment did not revoke course creation.');
}
echo "Teacher creation grant and revocation passed; test account restored.\n";
