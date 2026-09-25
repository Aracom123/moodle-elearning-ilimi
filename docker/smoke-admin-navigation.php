<?php
// Verify primary navigation for administrators, learners and teachers.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

$menu = [
    'moremenu' => ['nodearray' => [
        ['key' => 'home'], ['key' => 'mycourses'], ['key' => 'myhome'],
    ]],
    'mobileprimarynav' => [
        ['key' => 'home'], ['key' => 'mycourses'], ['key' => 'myhome'],
    ],
];

foreach ([
    'qa.admin.isp.20260925' => ['mycourses', 2],
    'student.ia.test' => ['myhome', 2],
    'qa.enseignant.editeur.isp.20260925' => [null, 3],
] as $username => [$hiddenkey, $expectedcount]) {
    $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
    \core\session\manager::set_user($user);
    $filtered = \theme_moove\local\profile_navigation::filter_primary_menu($menu);
    foreach (['moremenu', 'mobileprimarynav'] as $part) {
        $items = $part === 'moremenu' ? $filtered['moremenu']['nodearray'] : $filtered['mobileprimarynav'];
        $keys = array_column($items, 'key');
        if (count($items) !== $expectedcount || ($hiddenkey !== null && in_array($hiddenkey, $keys, true))) {
            throw new RuntimeException("Unexpected {$part} items for {$username}.");
        }
    }
    echo "[OK] {$username}\n";
}
