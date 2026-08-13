<?php
// Render the analytics page as an administrator without creating data.

define('CLI_SCRIPT', true);

require dirname(__DIR__) . '/config.php';

\core\session\manager::set_user(get_admin());

$_GET['courseid'] = 0;
$_GET['period'] = 90;
$_REQUEST = $_GET;

ob_start();
require $CFG->dirroot . '/report/isplearninganalytics/index.php';
$html = ob_get_clean();

$expectations = [
    'Titre du rapport' => 'Analytique des apprentissages ISP',
    'Résumé par cours' => 'Vue par cours',
    'Détail apprenants' => 'Détail par apprenant',
    'Temps actif' => 'Temps actif moyen',
    'Note moyenne' => 'Note moyenne',
    'Audit' => 'Audit et traçabilité',
    'Export CSV' => 'Exporter les données CSV',
];

$failed = 0;
foreach ($expectations as $label => $needle) {
    $passed = str_contains($html, $needle);
    echo ($passed ? '[OK]   ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$passed) {
        $failed++;
    }
}

echo PHP_EOL . strlen($html) . ' octets HTML rendus, ' . $failed . ' échec(s).' . PHP_EOL;
exit($failed === 0 ? 0 : 1);

