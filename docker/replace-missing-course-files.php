<?php
// Replace broken PPTX resources with newly authored, course-specific supports.
define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$replacements = [
    60 => 'PSH_Psychologie_Humaine.pptx',
    56 => 'EPID101_Introduction_Epidemiologie.pptx',
    58 => 'BIOS201_Biostatistiques.pptx',
    57 => 'EPID301_Maladies_Chroniques.pptx',
    61 => 'SCOM101_Sante_Communautaire.pptx',
    59 => 'PROM201_Promotion_Sante.pptx',
    76 => 'RHS_Niamey_Groupe5_v3.pptx',
];
$fs = get_file_storage();
function support_blob_exists(stored_file $file): bool {
    global $CFG;
    $hash = $file->get_contenthash();
    return is_file($CFG->dataroot . '/filedir/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash);
}
foreach ($replacements as $cmid => $filename) {
    $source = __DIR__ . '/generated-supports/' . $filename;
    if (!is_file($source)) { throw new RuntimeException("Missing generated support: {$source}"); }
    $cm = get_coursemodule_from_id('resource', $cmid, 0, false, MUST_EXIST);
    $context = context_module::instance($cmid);
    $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'id', false);
    $current = reset($files);
    if ($current && $current->get_contenthash() === sha1_file($source) && support_blob_exists($current)) {
        echo "EXISTS {$cmid}: {$filename}\n";
        continue;
    }
    // Only replace the known missing file; never discard an instructor's live upload.
    if ($current && support_blob_exists($current)) {
        echo "SKIP live upload {$cmid}: {$current->get_filename()}\n";
        continue;
    }
    foreach ($files as $file) { $file->delete(); }
    $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => 'mod_resource',
        'filearea' => 'content',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename,
        'userid' => get_admin()->id,
        'source' => 'Support pédagogique créé pour remplacer un fichier manquant',
        'license' => 'allrightsreserved',
    ], $source);
    $resource = $DB->get_record('resource', ['id' => $cm->instance], '*', MUST_EXIST);
    if ($cmid === 76) { $resource->name = 'Support de diagnostic communautaire (nouveau)'; }
    $resource->intro = '<p>Nouveau support pédagogique créé pour remplacer un fichier manquant. '
        . 'Il synthétise le cours et propose une application pratique.</p>';
    $resource->introformat = FORMAT_HTML;
    $DB->update_record('resource', $resource);
    echo "REPLACED {$cmid}: {$filename}\n";
}
