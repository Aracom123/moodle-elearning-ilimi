<?php
// Read-only inventory of the teaching sequence for content repair.
define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

foreach ([2, 3, 4, 5, 6, 7, 8] as $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], 'id,fullname,summary', MUST_EXIST);
    echo "COURSE {$courseid}: {$course->fullname}\n";
    echo 'SUMMARY ' . trim(preg_replace('/\s+/', ' ', strip_tags($course->summary))) . "\n";
    $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section', 'id,section,name,summary,sequence');
    foreach ($sections as $section) {
        echo "SECTION {$section->section}: {$section->name}\n";
        echo '  SUMMARY ' . trim(preg_replace('/\s+/', ' ', strip_tags($section->summary))) . "\n";
        foreach (array_filter(explode(',', $section->sequence)) as $cmid) {
            $record = $DB->get_record_sql('SELECT cm.id, cm.instance, m.name AS modname FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module WHERE cm.id = ?', [$cmid]);
            if (!$record) { continue; }
            $table = $record->modname;
            if (!in_array($table, ['page', 'resource', 'url', 'quiz', 'assign', 'forum', 'attendance', 'customcert', 'hvp', 'h5pactivity'])) { continue; }
            $activity = $DB->get_record($table, ['id' => $record->instance]);
            if (!$activity) { continue; }
            echo "  ACTIVITY {$record->id} {$table}: {$activity->name}\n";
            foreach (['intro', 'content'] as $field) {
                if (!empty($activity->$field)) {
                    echo '    ' . strtoupper($field) . ' ' . substr(trim(preg_replace('/\s+/', ' ', strip_tags($activity->$field))), 0, 850) . "\n";
                }
            }
        }
    }
}
