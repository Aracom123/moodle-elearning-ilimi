<?php
// Remove the unused Moodle non-editing teacher role from this ISP installation.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

$admin = get_admin();
\core\session\manager::set_user($admin);

$role = $DB->get_record('role', ['shortname' => 'teacher']);
if (!$role) {
    echo "Le rôle enseignant non éditeur est déjà absent.\n";
    exit(0);
}
if ($role->archetype !== 'teacher') {
    throw new RuntimeException('Le rôle « teacher » ne correspond pas à l’archétype Moodle attendu.');
}

// Do not silently remove access from real staff if this script is reused elsewhere.
$assignments = $DB->get_records_sql(
    "SELECT ra.id, u.username
       FROM {role_assignments} ra
       JOIN {user} u ON u.id = ra.userid
      WHERE ra.roleid = :roleid",
    ['roleid' => $role->id]
);
$testaccount = 'qa.enseignant.non.editeur.isp.20260925';
foreach ($assignments as $assignment) {
    if ($assignment->username !== $testaccount) {
        throw new RuntimeException('Un utilisateur réel possède encore ce rôle : ' . $assignment->username
            . '. Réviser son affectation avant suppression.');
    }
}

delete_role((int)$role->id);
echo "Rôle enseignant non éditeur supprimé ; " . count($assignments)
    . " affectation de test retirée. Le compte test est conservé.\n";
