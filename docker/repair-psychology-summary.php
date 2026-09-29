<?php
// Replace a broken embedded course-summary video with a useful course synopsis.
define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());
$course = $DB->get_record('course', ['id' => 2], '*', MUST_EXIST);
$filename = 'WhatsApp Video 2026-04-05 at 09.14.45.mp4';
if (str_contains((string)$course->summary, 'WhatsApp Video 2026-04-05')) {
    $course->summary = '<p>Introduction à la psychologie humaine : comportements, processus mentaux, '
        . 'méthodes d’observation et d’expérimentation, grands courants et applications à la santé.</p>'
        . '<p>Le cours comprend des leçons, des exercices et un nouveau support de présentation téléchargeable.</p>';
    $course->summaryformat = FORMAT_HTML;
    if ($course->fullname === 'Psycologie humaine') { $course->fullname = 'Psychologie humaine'; }
    $DB->update_record('course', $course);
    rebuild_course_cache($course->id, true);
    echo "REPAIRED course 2 summary\n";
}
$context = context_course::instance(2);
$files = get_file_storage()->get_area_files($context->id, 'course', 'summary', 0, 'id', false);
foreach ($files as $file) {
    if ($file->get_filename() !== $filename) { continue; }
    $hash = $file->get_contenthash();
    $path = $CFG->dataroot . '/filedir/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
    if (is_file($path)) { throw new RuntimeException('Course summary video became available; preserving it.'); }
    $file->delete();
    echo "REMOVED missing summary video record\n";
}
