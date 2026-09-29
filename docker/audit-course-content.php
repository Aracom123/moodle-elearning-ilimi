<?php
// Read-only inventory of course sections and Moodle-managed file blobs.
define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

$courses = $DB->get_records_select('course', 'id <> :siteid', ['siteid' => SITEID], 'id', 'id,fullname,visible');
foreach ($courses as $course) {
    echo "COURSE\t{$course->id}\t{$course->visible}\t" . str_replace(["\t", "\n"], ' ', $course->fullname) . "\n";
    $sections = $DB->get_records('course_sections', ['course' => $course->id], 'section', 'id,section,name,summary,visible,sequence');
    foreach ($sections as $section) {
        $moduleids = array_filter(explode(',', (string) $section->sequence), 'strlen');
        if ((int) $section->section === 0 && !$moduleids && trim(strip_tags((string) $section->summary)) === '') {
            continue;
        }
        $state = $moduleids ? 'populated' : (trim(strip_tags((string) $section->summary)) !== '' ? 'summary-only' : 'empty');
        echo "SECTION\t{$course->id}\t{$section->section}\t{$section->visible}\t{$state}\t" . count($moduleids)
            . "\t" . str_replace(["\t", "\n"], ' ', (string) $section->name) . "\n";
    }
}

$files = $DB->get_records_sql("SELECT f.id, f.contextid, f.component, f.filearea, f.filename, f.contenthash, f.filesize,
                                    cm.course, cm.id AS cmid, m.name AS modulename
                               FROM {files} f
                          LEFT JOIN {context} ctx ON ctx.id = f.contextid AND ctx.contextlevel = :modulelevel
                          LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid
                          LEFT JOIN {modules} m ON m.id = cm.module
                              WHERE f.filename <> '.' AND f.filesize > 0
                           ORDER BY f.id", ['modulelevel' => CONTEXT_MODULE]);
$filecount = 0;
$missing = 0;
foreach ($files as $file) {
    $filecount++;
    $hash = $file->contenthash;
    $path = $CFG->dataroot . '/filedir/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
    if (!is_file($path) || filesize($path) !== (int) $file->filesize) {
        $missing++;
        echo "MISSING\t{$file->id}\t{$file->course}\t{$file->cmid}\t{$file->modulename}\t{$file->component}\t{$file->filearea}\t{$file->filesize}\t{$hash}\t"
            . str_replace(["\t", "\n"], ' ', $file->filename) . "\n";
    }
}
echo "TOTAL\tfiles={$filecount}\tmissing={$missing}\n";
