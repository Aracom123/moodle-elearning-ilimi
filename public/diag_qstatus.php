<?php
require_once('../config.php');
require_login(); if (!is_siteadmin()) die('no');
header('Content-Type: text/plain; charset=utf-8');
global $DB;
$course = $DB->get_record('course', ['shortname'=>'SCOM101']);
$ctx = context_course::instance($course->id);
$cat = $DB->get_record('question_categories', ['contextid'=>$ctx->id, 'name'=>'Questions SCOM101']);
$rows = $DB->get_records_sql(
  "SELECT qv.id, q.name, qv.version, qv.status, qbe.id AS qbeid
     FROM {question_versions} qv
     JOIN {question} q ON q.id = qv.questionid
     JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
    WHERE qbe.questioncategoryid = ?
 ORDER BY qbe.id, qv.version", [$cat->id]);
foreach ($rows as $r) {
  echo "qbe={$r->qbeid} v{$r->version} status={$r->status} : {$r->name}\n";
}
