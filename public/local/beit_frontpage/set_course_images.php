<?php
/**
 * Script à exécuter UNE SEULE FOIS : attache une image (overviewfiles) à
 * chaque cours, à partir des fichiers du dossier course_images/.
 *
 * Accéder à : http://moodle.local/local/beit_frontpage/set_course_images.php
 * (Connecté comme administrateur.)
 *
 * >>> SUPPRIMER ce fichier ET le dossier course_images/ après exécution. <<<
 *
 * @package   local_beit_frontpage
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

// Mapping : id du cours => fichier image local.
$map = [
    2 => 'course_2.jpg', // Psychologie humaine
    5 => 'course_5.jpg', // Épidémiologie des Maladies Chroniques
    4 => 'course_4.jpg', // Biostatistiques Appliquées
    3 => 'course_3.jpg', // Introduction à l'Épidémiologie
    7 => 'course_7.jpg', // Stratégies de Promotion de la Santé
    6 => 'course_6.jpg', // Principes de Santé Communautaire
];

$imgdir = __DIR__ . '/course_images';
$fs = get_file_storage();
$report = [];

foreach ($map as $courseid => $filename) {
    $localpath = $imgdir . '/' . $filename;

    // Vérifier l'existence du cours.
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        $report[] = "⚠ Cours id=$courseid introuvable — ignoré.";
        continue;
    }
    if (!is_readable($localpath)) {
        $report[] = "⚠ Image $filename introuvable pour le cours « {$course->fullname} » — ignoré.";
        continue;
    }

    $context = context_course::instance($courseid);

    // Supprimer toute image overview existante pour éviter les doublons.
    $fs->delete_area_files($context->id, 'course', 'overviewfiles', 0);

    // Créer le nouveau fichier overview.
    $filerecord = [
        'contextid' => $context->id,
        'component' => 'course',
        'filearea'  => 'overviewfiles',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename,
    ];
    $fs->create_file_from_pathname($filerecord, $localpath);

    $report[] = "✓ Image attachée au cours « {$course->fullname} » (id=$courseid).";
}

// Pas de purge de cache ici : les fichiers overviewfiles sont lus directement,
// et purge_all_caches() déconnecterait la session admin.

// Affichage du rapport.
echo '<div style="font-family:system-ui,sans-serif;max-width:680px;margin:3rem auto;'
   . 'padding:2rem;border:1px solid #e2e8f0;border-radius:12px;line-height:1.7;">';
echo '<h2 style="margin-top:0;color:#16a34a;">Images des cours — rapport</h2><ul>';
foreach ($report as $line) {
    echo '<li>' . s($line) . '</li>';
}
echo '</ul>';
echo '<p style="background:#fef2f2;border:1px solid #fecaca;padding:1rem;border-radius:8px;color:#b91c1c;">'
   . '<strong>⚠ Important :</strong> supprimez maintenant ce fichier et le dossier des images :<br>'
   . '<code>set_course_images.php</code> et <code>course_images/</code></p>';
echo '<p><a href="' . (new moodle_url('/'))->out() . '">→ Voir la page d\'accueil</a></p>';
echo '</div>';
