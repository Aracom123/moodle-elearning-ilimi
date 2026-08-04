<?php
require_once('../config.php');
require_login(); if (!is_siteadmin()) die('no');
header('Content-Type: text/plain; charset=utf-8');
global $DB;
$q = $DB->get_record('quiz', ['id'=>2]);
echo "sumgrades={$q->sumgrades} grade={$q->grade}\n";
$gi = $DB->get_record('grade_items', ['itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>2]);
echo "gradepass=" . ($gi ? $gi->gradepass : 'n/a') . "\n";
echo "slots=" . $DB->count_records('quiz_slots', ['quizid'=>2]) . "\n";
