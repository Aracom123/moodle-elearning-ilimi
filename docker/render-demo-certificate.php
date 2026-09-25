<?php
// Render an already-issued test certificate for visual and QR verification.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

\core\session\manager::set_user(get_admin());

$certificate = $DB->get_record('customcert', ['name' => 'Certificat final — parcours IA'], '*', MUST_EXIST);
$learner = $DB->get_record('user', ['username' => 'student.ia.test'], '*', MUST_EXIST);
$issue = $DB->get_record('customcert_issues', [
    'customcertid' => $certificate->id,
    'userid' => $learner->id,
], 'id', MUST_EXIST);
$template = \mod_customcert\template::from_record(
    $DB->get_record('customcert_templates', ['id' => $certificate->templateid], '*', MUST_EXIST)
);
$pdf = \mod_customcert\service\pdf_generation_service::create()->generate_pdf(
    $template, false, (int)$learner->id, true
);

$outputdir = dirname(__DIR__) . '/output/pdf';
if (!is_dir($outputdir) && !mkdir($outputdir, 0775, true) && !is_dir($outputdir)) {
    throw new RuntimeException("Cannot create output directory: {$outputdir}");
}
$output = $outputdir . '/isp-certificat-ia-demo.pdf';
if (file_put_contents($output, $pdf) === false) {
    throw new RuntimeException("Cannot write certificate preview: {$output}");
}
echo "Rendered issued certificate to {$output}\n";
