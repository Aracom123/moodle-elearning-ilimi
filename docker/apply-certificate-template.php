<?php
// Apply the shared ISP design to certificates already installed in this Moodle instance.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';
require_once __DIR__ . '/certificate-template.php';

\core\session\manager::set_user(get_admin());

$names = [
    'Certificat de réussite ISP',
    'Certificat final — parcours IA',
];
foreach ($names as $name) {
    $certificate = $DB->get_record('customcert', ['name' => $name]);
    if (!$certificate) {
        continue;
    }
    isp_apply_certificate_template($certificate);
    echo "Updated certificate template: {$name}\n";
}
