<?php
// Idempotently add sourced teaching pages to previously empty course sections.
define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/lib/resourcelib.php';

\core\session\manager::set_user(get_admin());
$definitions = json_decode(file_get_contents(__DIR__ . '/course-content-repair.json'), true, 512, JSON_THROW_ON_ERROR);
$pageid = $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST);
$created = 0;
foreach ($definitions as $definition) {
    $course = get_course($definition['course']);
    $section = $DB->get_record('course_sections', [
        'course' => $course->id, 'section' => $definition['section'],
    ], '*', MUST_EXIST);
    $name = $definition['title'];
    if ($DB->record_exists('page', ['course' => $course->id, 'name' => $name])) {
        echo "EXISTS {$course->id}/{$definition['section']}: {$name}\n";
        continue;
    }
    // Avoid changing an instructor's populated section if this script runs after other edits.
    if (trim((string)$section->sequence) !== '') {
        echo "SKIP populated {$course->id}/{$definition['section']}\n";
        continue;
    }
    $section->name = $definition['section'] . '. ' . $name;
    $section->summary = '<p>' . s($definition['objective']) . '</p>';
    $section->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $section);
    $content = '<h2>Objectif</h2><p>' . s($definition['objective']) . '</p>'
        . '<h2>Leçon</h2><p>' . s($definition['lesson']) . '</p>'
        . '<h2>Application</h2><p>' . s($definition['exercise']) . '</p>'
        . '<h2>Pour approfondir</h2><p><a href="' . s($definition['reference']) . '">'
        . s($definition['reference']) . '</a></p>';
    add_moduleinfo((object)[
        'course' => $course->id,
        'module' => $pageid,
        'modulename' => 'page',
        'section' => $definition['section'],
        'name' => $name,
        'intro' => '<p>' . s($definition['objective']) . '</p>',
        'introformat' => FORMAT_HTML,
        'content' => $content,
        'contentformat' => FORMAT_HTML,
        'revision' => 1,
        'display' => RESOURCELIB_DISPLAY_OPEN,
        'printintro' => 1,
        'visible' => 1,
        'completion' => COMPLETION_TRACKING_AUTOMATIC,
        'completionview' => COMPLETION_VIEW_REQUIRED,
    ], $course, null);
    $created++;
    echo "CREATED {$course->id}/{$definition['section']}: {$name}\n";
}
echo "CREATED TOTAL: {$created}\n";
