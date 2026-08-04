<?php
require_once('../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_login(); if (!is_siteadmin()) die('no');
header('Content-Type: text/plain; charset=utf-8');
global $DB;

$course = $DB->get_record('course', ['shortname'=>'SCOM101']);
$quiz = $DB->get_record('quiz', ['course'=>$course->id, 'name'=>'Quiz noté — Santé Communautaire']);
if (!$quiz) die("Quiz introuvable.\n");

$mod = $DB->get_record('modules', ['name'=>'quiz']);
$cm = $DB->get_record('course_modules', ['module'=>$mod->id, 'instance'=>$quiz->id]);
echo "Quiz id={$quiz->id}, cm={$cm->id}\n";

// Combien de slots (questions ajoutées) ?
$nslots = $DB->count_records('quiz_slots', ['quizid'=>$quiz->id]);
echo "Questions actuellement dans le quiz (slots) : $nslots\n";

// Recalcul des sumgrades avec la bonne API Moodle 5.2.
$quizobj = \mod_quiz\quiz_settings::create($quiz->id);
\mod_quiz\grade_calculator::create($quizobj)->recompute_quiz_sumgrades();
echo "sumgrades recalculé.\n";

// Note de passage 5/10
$gi = $DB->get_record('grade_items', ['itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>$quiz->id,'courseid'=>$course->id]);
if ($gi) { $gi->gradepass = 5.0; $DB->update_record('grade_items', $gi); echo "Note de passage = 5/10\n"; }
else { echo "(grade_item pas encore créé)\n"; }

rebuild_course_cache($course->id, true);
$quiz2 = $DB->get_record('quiz', ['id'=>$quiz->id]);
echo "sumgrades = {$quiz2->sumgrades}, grade = {$quiz2->grade}\n";
echo "OK. Voir : /mod/quiz/view.php?id={$cm->id}\n";
@unlink(__FILE__);
