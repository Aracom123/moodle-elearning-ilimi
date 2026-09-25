<?php
// Read-only smoke checks for the role-specific dashboard content.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

$admin = get_admin();
$teacher = $DB->get_record('user', ['username' => 'enseignant.demo.isp'], '*', MUST_EXIST);
$learner = $DB->get_record('user', ['username' => 'student.ia.test'], '*', MUST_EXIST);

foreach ([
    [$admin, 'admin', 'Pilotage de la plateforme'],
    [$teacher, 'teacher', 'Statistiques des cours'],
    [$learner, 'learner', 'Mes certifications'],
] as [$user, $expectedrole, $expectedtext]) {
    \core\session\manager::set_user($user);
    $result = \theme_moove\local\role_dashboard::render((int) $user->id);
    if ($result['role'] !== $expectedrole || !str_contains($result['html'], $expectedtext)) {
        throw new RuntimeException("Dashboard {$expectedrole} incomplet.");
    }
    if ($expectedrole !== 'admin') {
        if (!str_contains($result['html'], '/my/courses.php')
                || !str_contains($result['html'], 'Voir mes cours')
                || str_contains($result['html'], 'Mes cours à gérer')
                || str_contains($result['html'], 'id="isp-courses-title"')) {
            throw new RuntimeException("Dashboard {$expectedrole} répète la liste des cours ou manque de navigation.");
        }
        if ($expectedrole === 'learner'
                && !str_contains($result['html'], '/my/courses.php#isp-my-courses-list')) {
            throw new RuntimeException('Le compteur « Cours suivis » ne pointe pas vers la liste des cours.');
        }
        if ($expectedrole === 'teacher') {
            foreach (['Inscriptions par cours', 'Progression moyenne', 'Étudiants actifs · 4 semaines'] as $charttitle) {
                if (!str_contains($result['html'], $charttitle)) {
                    throw new RuntimeException("Graphique enseignant absent : {$charttitle}.");
                }
            }
            if (str_contains($result['html'], 'Pilotage de la plateforme')) {
                throw new RuntimeException('Le tableau de bord enseignant contient des données administrateur.');
            }
        }
    } else if (!str_contains($result['html'], 'admin_detail.php?view=active')
            || !str_contains($result['html'], 'admin_detail.php?view=certificates')) {
        throw new RuntimeException('Dashboard administrateur sans accès aux détails des indicateurs.');
    }
    echo "[OK] {$expectedrole}\n";
}
