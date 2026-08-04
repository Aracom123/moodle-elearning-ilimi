<?php
/**
 * Crée le module Quiz dans SCOM101 et y ajoute les questions déjà présentes
 * dans la catégorie "Questions SCOM101". Version corrigée (contexte module).
 */
require_once('../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir . '/gradelib.php');

require_login();
if (!is_siteadmin()) die('Accès refusé.');
@set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');
echo "<style>body{font-family:Arial;max-width:900px;margin:40px auto}li{margin:4px 0}.ok{color:#2E7D32}.err{color:#c0392b}h2{color:#1F3A5F}</style>";
echo "<h2>🧪 Quiz SCOM101 — assemblage</h2><ul>";

global $DB;
$course = $DB->get_record('course', ['shortname' => 'SCOM101'], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

if ($DB->get_record('quiz', ['course'=>$course->id, 'name'=>'Quiz noté — Santé Communautaire'])) {
    die("<li class='err'>Le quiz existe déjà.</li></ul>");
}

// Récupérer les questions existantes (dernière version) de la catégorie.
$cat = $DB->get_record('question_categories', ['contextid'=>$coursecontext->id, 'name'=>'Questions SCOM101'], '*', MUST_EXIST);
$sql = "SELECT q.id, q.name, q.defaultmark
          FROM {question} q
          JOIN {question_versions} qv ON qv.questionid = q.id
          JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
         WHERE qbe.questioncategoryid = :cat
           AND qv.version = (SELECT MAX(v.version) FROM {question_versions} v WHERE v.questionbankentryid = qbe.id)
      ORDER BY q.id ASC";
$questions = $DB->get_records_sql($sql, ['cat' => $cat->id]);
echo "<li class='ok'>Questions trouvées : " . count($questions) . "</li>";
if (!$questions) die("<li class='err'>Aucune question. Relancez d'abord create_quiz.php.</li></ul>");

// ── Créer le module Quiz ──────────────────────────────────────────────
$mod = $DB->get_record('modules', ['name'=>'quiz'], '*', MUST_EXIST);
course_create_sections_if_missing($course, 1);
$sec = $DB->get_record('course_sections', ['course'=>$course->id, 'section'=>1]);

$quiz = new stdClass();
$quiz->course = $course->id;
$quiz->name = 'Quiz noté — Santé Communautaire';
$quiz->intro = '<p>Évaluez vos connaissances. Note de passage : <strong>50%</strong>. Tentatives illimitées.</p>';
$quiz->introformat = FORMAT_HTML;
$quiz->timeopen=0; $quiz->timeclose=0; $quiz->timelimit=0;
$quiz->overduehandling='autosubmit'; $quiz->graceperiod=0;
$quiz->preferredbehaviour='deferredfeedback';
$quiz->canredoquestions=0; $quiz->attempts=0; $quiz->attemptonlast=0;
$quiz->grademethod=1; $quiz->decimalpoints=2; $quiz->questiondecimalpoints=-1;
$quiz->reviewattempt=69888; $quiz->reviewcorrectness=4352; $quiz->reviewmaxmarks=4352;
$quiz->reviewmarks=4352; $quiz->reviewspecificfeedback=4352; $quiz->reviewgeneralfeedback=4352;
$quiz->reviewrightanswer=4352; $quiz->reviewoverallfeedback=4352;
$quiz->questionsperpage=1; $quiz->navmethod='free'; $quiz->shuffleanswers=1;
$quiz->sumgrades=0; $quiz->grade=10;
$quiz->timecreated=time(); $quiz->timemodified=time();
$quiz->password=''; $quiz->subnet=''; $quiz->browsersecurity='-';
$quiz->delay1=0; $quiz->delay2=0; $quiz->showuserpicture=0; $quiz->showblocks=0;
$quiz->completionattemptsexhausted=0; $quiz->completionminattempts=0;
$quiz->allowofflineattempts=0;
$quiz->id = $DB->insert_record('quiz', $quiz);
echo "<li class='ok'>✅ Instance quiz créée (id {$quiz->id})</li>";

// feedback global obligatoire
$fb = new stdClass();
$fb->quizid=$quiz->id; $fb->feedbacktext='Quiz terminé. Merci !';
$fb->feedbacktextformat=FORMAT_HTML; $fb->mingrade=0; $fb->maxgrade=10.01;
$DB->insert_record('quiz_feedback', $fb);

// course_module
$cm = new stdClass();
$cm->course=$course->id; $cm->module=$mod->id; $cm->instance=$quiz->id;
$cm->section=$sec->id; $cm->visible=1; $cm->visibleold=1; $cm->added=time();
$cm->completion=2; $cm->completiongradeitemnumber=0; $cm->completionpassgrade=1; $cm->completionview=0;
$cm->id = $DB->insert_record('course_modules', $cm);
$seq = trim($sec->sequence ?? ''); $seq = $seq ? $seq.','.$cm->id : (string)$cm->id;
$DB->set_field('course_sections', 'sequence', $seq, ['id'=>$sec->id]);
echo "<li class='ok'>✅ Module créé (cm {$cm->id})</li>";

// IMPORTANT : forcer la création du contexte du module AVANT d'ajouter les questions.
$modcontext = context_module::instance($cm->id);
echo "<li class='ok'>✅ Contexte module initialisé (id {$modcontext->id})</li>";

// Recharger un objet quiz complet AVEC cmid.
$quizobj = $DB->get_record('quiz', ['id'=>$quiz->id]);
$quizobj->cmid = $cm->id;

// ── Ajouter les questions ─────────────────────────────────────────────
$added = 0;
foreach ($questions as $q) {
    try {
        quiz_add_quiz_question($q->id, $quizobj, 0, 1);
        $added++;
    } catch (Throwable $e) {
        echo "<li class='err'>❌ Question {$q->name} : ".$e->getMessage()."</li>";
    }
}
echo "<li class='ok'>✅ $added questions ajoutées</li>";

// Recalcul des notes max.
quiz_update_sumgrades($quizobj);

// Note de passage 5/10.
$gi = $DB->get_record('grade_items', ['itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>$quiz->id,'courseid'=>$course->id]);
if ($gi) { $gi->gradepass = 5.0; $DB->update_record('grade_items', $gi); echo "<li class='ok'>✅ Note de passage 5/10</li>"; }

rebuild_course_cache($course->id, true);
echo "</ul><p style='font-size:16px'>🎉 <a href='/mod/quiz/view.php?id={$cm->id}'>Voir le quiz</a></p>";
@unlink(__FILE__);
