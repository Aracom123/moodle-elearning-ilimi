<?php
require_once('../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_login(); if (!is_siteadmin()) die('no');
header('Content-Type: text/plain; charset=utf-8');
global $DB;

$cmid = 74;
$quizid = 2;
$course = $DB->get_record('course', ['shortname'=>'SCOM101']);

// quiz_settings via cmid (la bonne méthode en 5.2).
$quizobj = \mod_quiz\quiz_settings::create_for_cmid($cmid);
\mod_quiz\grade_calculator::create($quizobj)->recompute_quiz_sumgrades();
echo "sumgrades recalculé via cmid $cmid.\n";

// Note de passage 5/10.
$gi = $DB->get_record('grade_items', ['itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>$quizid,'courseid'=>$course->id]);
if ($gi) { $gi->gradepass = 5.0; $DB->update_record('grade_items', $gi); echo "Note de passage = 5/10.\n"; }
else { echo "grade_item absent (sera créé au 1er passage).\n"; }

rebuild_course_cache($course->id, true);
$q = $DB->get_record('quiz', ['id'=>$quizid]);
echo "Résultat -> sumgrades={$q->sumgrades}, grade={$q->grade}\n";
echo "Voir le quiz : /mod/quiz/view.php?id=$cmid\n";
@unlink(__FILE__);
