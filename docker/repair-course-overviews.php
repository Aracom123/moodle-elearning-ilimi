<?php
// Restore missing course-card images from the checked-in assets and label sections.
define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$fs = get_file_storage();
foreach ([2, 3, 4, 5, 6, 7] as $courseid) {
    $context = context_course::instance($courseid);
    $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'id', false);
    $livefile = false;
    foreach ($files as $file) {
        $hash = $file->get_contenthash();
        $path = $CFG->dataroot . '/filedir/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
        if (is_file($path)) { $livefile = true; break; }
    }
    if ($livefile) { echo "SKIP live image {$courseid}\n"; continue; }
    $filename = "course_{$courseid}.jpg";
    $source = $CFG->dirroot . '/local/beit_frontpage/course_images/' . $filename;
    if (!is_file($source)) { throw new RuntimeException("Missing course image: {$source}"); }
    foreach ($files as $file) { $file->delete(); }
    $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => 'course',
        'filearea' => 'overviewfiles',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename,
        'userid' => get_admin()->id,
        'license' => 'allrightsreserved',
    ], $source);
    echo "RESTORED image {$courseid}\n";
}

$names = [
    6 => [0 => 'Informations générales', 1 => '1. Fondements de la santé communautaire', 4 => '4. Ressources et glossaire'],
    8 => [0 => 'Informations générales', 1 => '1. Méthodologie du diagnostic communautaire'],
];
foreach ($names as $courseid => $sections) {
    foreach ($sections as $number => $name) {
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $number], '*', MUST_EXIST);
        if ($section->name === $name) { continue; }
        if (trim((string)$section->name) !== '' && !($courseid === 8 && $number === 1 && trim($section->name) === 'Chapaitre 1 :')) {
            echo "SKIP instructor title {$courseid}/{$number}\n";
            continue;
        }
        $section->name = $name;
        $DB->update_record('course_sections', $section);
        echo "LABELED {$courseid}/{$number}: {$name}\n";
    }
}
