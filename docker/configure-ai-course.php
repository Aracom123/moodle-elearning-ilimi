<?php
// Idempotent end-to-end AI course for testing the learner journey.

if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require_once dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/lib/resourcelib.php';
require_once $CFG->dirroot . '/lib/questionlib.php';
require_once $CFG->dirroot . '/completion/criteria/completion_criteria_activity.php';
require_once $CFG->dirroot . '/completion/completion_aggregation.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/format.php';
require_once $CFG->dirroot . '/question/format/xml/format.php';
require_once $CFG->dirroot . '/user/lib.php';

global $DB, $CFG;

$admin = get_admin();
\core\session\manager::set_user($admin);

$aimessages = [];
$shortname = 'IA-SANTE-PUBLIQUE-TEST';
$idnumber = 'IA-SANTE-PUBLIQUE-TEST';
$coursename = 'Intelligence artificielle pour la santé publique — parcours pratique';

// Reuse the first visible category, which is already used by the local project.
$category = $DB->get_record_select('course_categories', 'visible = 1', [], 'id', IGNORE_MULTIPLE);
if (!$category) {
    $category = $DB->get_record('course_categories', [], 'id', IGNORE_MULTIPLE);
}
if (!$category) {
    throw new moodle_exception('Aucune catégorie de cours disponible.');
}

$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    $course = create_course((object) [
        'category' => $category->id,
        'fullname' => $coursename,
        'shortname' => $shortname,
        'idnumber' => $idnumber,
        'summary' => '<p>Un parcours guidé pour comprendre les bases de l’intelligence artificielle, '
            . 'évaluer la qualité des données et concevoir un usage responsable en santé publique.</p>'
            . '<p><strong>Objectif de test :</strong> inscription libre, activités suivies, quiz final, '
            . 'complétion du cours et téléchargement du certificat.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 5,
        'enablecompletion' => COMPLETION_ENABLED,
        'showcompletionconditions' => 1,
        'visible' => 1,
        'lang' => 'fr',
        'newsitems' => 5,
        'maxbytes' => 0,
    ]);
    $aimessages[] = 'Cours IA créé.';
} else {
    $course->fullname = $coursename;
    $course->idnumber = $idnumber;
    $course->visible = 1;
    $course->enablecompletion = COMPLETION_ENABLED;
    $course->showcompletionconditions = 1;
    $DB->update_record('course', $course);
    course_create_sections_if_missing($course, [0, 1, 2, 3, 4, 5]);
}

$course = get_course($course->id);
course_create_sections_if_missing($course, [0, 1, 2, 3, 4, 5]);
$format = course_get_format($course->id);
$format->update_course_format_options(['numsections' => 5]);

$sectiondefinitions = [
    1 => [
        'name' => '1. Fondamentaux de l’intelligence artificielle',
        'summary' => '<p>Comprendre les notions essentielles avant de choisir un outil d’IA.</p>',
    ],
    2 => [
        'name' => '2. Données, modèles et qualité',
        'summary' => '<p>Relier qualité des données, biais, validation et performance des modèles.</p>',
    ],
    3 => [
        'name' => '3. Usages responsables en santé publique',
        'summary' => '<p>Identifier les risques, les garde-fous et les responsabilités humaines.</p>',
    ],
    4 => [
        'name' => '4. Atelier : concevoir un cas d’usage',
        'summary' => '<p>Passer d’un besoin métier à un cas d’usage testable et mesurable.</p>',
    ],
    5 => [
        'name' => '5. Évaluation et certificat final',
        'summary' => '<p>Valider les acquis avec le quiz final puis générer le certificat nominatif.</p>',
    ],
];
foreach ($sectiondefinitions as $sectionnumber => $definition) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnumber], '*', MUST_EXIST);
    if ($section->name !== $definition['name'] || $section->summary !== $definition['summary']) {
        $section->name = $definition['name'];
        $section->summary = $definition['summary'];
        $section->summaryformat = FORMAT_HTML;
        $DB->update_record('course_sections', $section);
    }
}

$pagemodule = $DB->get_record('modules', ['name' => 'page'], '*', MUST_EXIST);
$pageactivities = [
    [
        'name' => 'Bienvenue et objectifs du parcours IA',
        'section' => 1,
        'intro' => '<p>Cette étape présente le parcours et les critères de réussite.</p>',
        'content' => '<h3>Votre mission</h3><p>À la fin du parcours, vous saurez distinguer les principales familles '
            . 'd’IA, repérer les risques liés aux données et formuler un cas d’usage responsable.</p>'
            . '<h3>Parcours de test</h3><ol><li>Ouvrir chaque ressource et valider sa consultation.</li>'
            . '<li>Répondre au quiz final avec au moins 70&nbsp;%.</li><li>Ouvrir le certificat et télécharger le PDF.</li></ol>',
    ],
    [
        'name' => 'Les notions clés : IA, apprentissage et modèles',
        'section' => 1,
        'intro' => '<p>Une synthèse accessible des concepts indispensables.</p>',
        'content' => '<h3>Trois idées à retenir</h3><ul><li><strong>IA :</strong> des systèmes qui produisent des prédictions, '
            . 'des recommandations ou du contenu.</li><li><strong>Apprentissage supervisé :</strong> un modèle apprend à partir '
            . 'd’exemples étiquetés.</li><li><strong>Modèle :</strong> une représentation calculée qui doit être évaluée dans son contexte.</li></ul>',
    ],
    [
        'name' => 'Données de santé : qualité, biais et confidentialité',
        'section' => 2,
        'intro' => '<p>Les données déterminent la pertinence et les limites des résultats.</p>',
        'content' => '<h3>Checklist qualité</h3><p>Vérifiez la complétude, la représentativité, la fraîcheur, la traçabilité '
            . 'et la sécurité des données. Documentez les valeurs manquantes et les populations sous-représentées.</p>'
            . '<p><strong>Règle :</strong> aucune donnée nominative ne doit être envoyée à un outil externe sans base légale, '
            . 'minimisation et contrôle institutionnel.</p>',
    ],
    [
        'name' => 'Usages responsables : décision humaine et transparence',
        'section' => 3,
        'intro' => '<p>Un résultat algorithmique aide la décision mais ne remplace pas la responsabilité professionnelle.</p>',
        'content' => '<h3>Garde-fous attendus</h3><ul><li>Définir l’usage autorisé et les limites.</li><li>Conserver une validation humaine.</li>'
            . '<li>Mesurer les erreurs et les écarts entre groupes.</li><li>Informer les personnes concernées et prévoir un recours.</li></ul>',
    ],
    [
        'name' => 'Atelier : formuler un cas d’usage mesurable',
        'section' => 4,
        'intro' => '<p>Préparez mentalement votre réponse au quiz à partir de cet exemple.</p>',
        'content' => '<h3>Exemple</h3><p>Un district souhaite prioriser les visites de sensibilisation. Un cas d’usage prudent '
            . 'consiste à proposer une liste de secteurs à examiner, avec indicateurs de qualité, validation par l’équipe et '
            . 'réévaluation régulière.</p><p>Définissez toujours : problème, utilisateurs, données, indicateur de succès, '
            . 'risques et responsable de la décision.</p>',
    ],
];
foreach ($pageactivities as $pageactivity) {
    if ($DB->record_exists('page', ['course' => $course->id, 'name' => $pageactivity['name']])) {
        continue;
    }
    add_moduleinfo((object) [
        'course' => $course->id,
        'module' => $pagemodule->id,
        'modulename' => 'page',
        'section' => $pageactivity['section'],
        'name' => $pageactivity['name'],
        'intro' => $pageactivity['intro'],
        'introformat' => FORMAT_HTML,
        'content' => $pageactivity['content'],
        'contentformat' => FORMAT_HTML,
        'revision' => 1,
        'display' => RESOURCELIB_DISPLAY_OPEN,
        'popupwidth' => 0,
        'popupheight' => 0,
        'printintro' => 1,
        'printlastmodified' => 0,
        'visible' => 1,
        'completion' => COMPLETION_TRACKING_AUTOMATIC,
        'completionview' => COMPLETION_VIEW_REQUIRED,
    ], $course, null);
}

// Create the final graded quiz once, then add three Moodle XML questions.
$quizmodule = $DB->get_record('modules', ['name' => 'quiz'], '*', MUST_EXIST);
$quizname = 'Quiz final — IA responsable en santé publique';
$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname]);
if (!$quiz) {
    $quizmoduleinfo = (object) [
        'course' => $course->id,
        'module' => $quizmodule->id,
        'modulename' => 'quiz',
        'section' => 5,
        'name' => $quizname,
        'intro' => '<p>Répondez aux trois questions. La réussite est fixée à 70&nbsp;%. Vous pouvez recommencer.</p>',
        'introformat' => FORMAT_HTML,
        'timeopen' => 0,
        'timeclose' => 0,
        'timelimit' => 0,
        'overduehandling' => 'autoabandon',
        'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'canredoquestions' => 0,
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 2,
        'questiondecimalpoints' => -1,
        'questionsperpage' => 1,
        'navmethod' => QUIZ_NAVMETHOD_FREE,
        'shuffleanswers' => 1,
        'grade' => 100,
        'quizpassword' => '',
        'password' => '',
        'subnet' => '',
        'browsersecurity' => '-',
        'delay1' => 0,
        'delay2' => 0,
        'showuserpicture' => 0,
        'showblocks' => 0,
        'completion' => COMPLETION_TRACKING_AUTOMATIC,
        'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
        'completionusegrade' => 1,
        'completionpassgrade' => 70,
        'completionunlocked' => 1,
        'visible' => 1,
    ];
    $quizcm = add_moduleinfo($quizmoduleinfo, $course, null);
    $quiz = $DB->get_record('quiz', ['id' => $quizcm->instance], '*', MUST_EXIST);
    $quiz->cmid = $quizcm->coursemodule;
    $quizcontext = context_module::instance($quizcm->coursemodule);
    $categoryquestion = question_get_default_category($quizcontext->id, true);

    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <question type="multichoice">
    <name><text>Définition opérationnelle</text></name>
    <questiontext format="html"><text><![CDATA[Quel énoncé décrit le mieux un système d’intelligence artificielle ?]]></text></questiontext>
    <defaultgrade>1</defaultgrade>
    <penalty>0.1</penalty>
    <hidden>0</hidden>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>abc</answernumbering>
    <answer fraction="100" format="html"><text><![CDATA[Un système qui produit une prédiction, une recommandation ou un contenu à partir de données et de règles apprises ou définies.]]></text></answer>
    <answer fraction="0" format="html"><text><![CDATA[Un tableur qui ne peut exécuter que des additions.]]></text></answer>
    <answer fraction="0" format="html"><text><![CDATA[Une base de données qui stocke uniquement des documents papier.]]></text></answer>
  </question>
  <question type="multichoice">
    <name><text>Données représentatives</text></name>
    <questiontext format="html"><text><![CDATA[Quelle pratique réduit le risque de biais dans un modèle de santé publique ?]]></text></questiontext>
    <defaultgrade>1</defaultgrade>
    <penalty>0.1</penalty>
    <hidden>0</hidden>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>abc</answernumbering>
    <answer fraction="100" format="html"><text><![CDATA[Évaluer la représentativité des données et mesurer les écarts de performance entre groupes.]]></text></answer>
    <answer fraction="0" format="html"><text><![CDATA[Supprimer les contrôles qualité pour aller plus vite.]]></text></answer>
    <answer fraction="0" format="html"><text><![CDATA[Utiliser uniquement le groupe le plus facile à mesurer.]]></text></answer>
  </question>
  <question type="multichoice">
    <name><text>Responsabilité humaine</text></name>
    <questiontext format="html"><text><![CDATA[Quel garde-fou est indispensable lorsqu’une IA aide une décision sensible ?]]></text></questiontext>
    <defaultgrade>1</defaultgrade>
    <penalty>0.1</penalty>
    <hidden>0</hidden>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>abc</answernumbering>
    <answer fraction="100" format="html"><text><![CDATA[Une validation humaine documentée, avec des limites d’usage et un suivi des erreurs.]]></text></answer>
    <answer fraction="0" format="html"><text><![CDATA[Donner au modèle le pouvoir de décider seul et sans recours.]]></text></answer>
    <answer fraction="0" format="html"><text><![CDATA[Masquer l’existence de l’outil aux personnes concernées.]]></text></answer>
  </question>
</quiz>
XML;
    $xmlfile = tempnam(sys_get_temp_dir(), 'moodle-ai-quiz-');
    file_put_contents($xmlfile, $xml);
    $qformat = new qformat_xml();
    $qformat->setCategory($categoryquestion);
    $qformat->setContexts([$quizcontext]);
    $qformat->setCourse($course);
    $qformat->setFilename($xmlfile);
    $qformat->setRealfilename($xmlfile);
    $qformat->displayprogress = false;
    $qformat->stoponerror = true;
    if (!$qformat->importprocess()) {
        @unlink($xmlfile);
        throw new moodle_exception('Échec de l’import des questions du quiz IA.');
    }
    @unlink($xmlfile);
    foreach ($qformat->questionids as $questionid) {
        quiz_add_quiz_question($questionid, $quiz);
    }
    $quizobject = \mod_quiz\quiz_settings::create($quiz->id);
    $quizobject->get_grade_calculator()->recompute_quiz_sumgrades();
    $quiz = $DB->get_record('quiz', ['id' => $quiz->id], '*', MUST_EXIST);
    $aimessages[] = 'Quiz final IA créé avec trois questions et seuil de réussite à 70 %.'.
        ' (somme : ' . $quiz->sumgrades . ')';
}

$quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
if ((int) $quizcm->completion !== COMPLETION_TRACKING_AUTOMATIC
        || (int) $quizcm->completionpassgrade !== 70
        || (int) $quizcm->completiongradeitemnumber !== 0) {
    $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, ['id' => $quizcm->id]);
    $DB->set_field('course_modules', 'completionpassgrade', 70, ['id' => $quizcm->id]);
    $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $quizcm->id]);
}

// Course completion is tied to passing the final quiz. Moodle stores the
// course-module id in moduleinstance (not the quiz instance id).
$criterionrecords = $DB->get_records('course_completion_criteria', [
    'course' => $course->id,
    'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
    'moduleinstance' => $quizcm->id,
]);
if (!$criterionrecords) {
    $criteriondata = (object) [
        'id' => $course->id,
        'criteria_activity' => [$quizcm->id => 1],
    ];
    $criterionapi = new completion_criteria_activity();
    $criterionapi->update_config($criteriondata);
    $aimessages[] = 'Complétion du cours IA reliée à la réussite du quiz final.';
} else if (count($criterionrecords) > 1) {
    // Remove duplicates left by a previous run of this provisioning script.
    $first = array_shift($criterionrecords);
    foreach ($criterionrecords as $duplicate) {
        $DB->delete_records('course_completion_criteria', ['id' => $duplicate->id]);
    }
}
foreach ([null, COMPLETION_CRITERIA_TYPE_ACTIVITY] as $criteriatype) {
    $params = ['course' => $course->id, 'criteriatype' => $criteriatype];
    $aggregation = completion_aggregation::fetch($params);
    if (!$aggregation) {
        $aggregation = new completion_aggregation($params, false);
    }
    $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
    $aggregation->save();
}

// Add a certificate activity, visible only after the final quiz is passed.
$certificateName = 'Certificat final — parcours IA';
$customcertmodule = $DB->get_record('modules', ['name' => 'customcert'], '*', MUST_EXIST);
$certificate = $DB->get_record('customcert', ['course' => $course->id, 'name' => $certificateName]);
if (!$certificate) {
    $certmoduleinfo = (object) [
        'course' => $course->id,
        'module' => $customcertmodule->id,
        'modulename' => 'customcert',
        'section' => 5,
        'name' => $certificateName,
        'intro' => '<p>Ce certificat devient disponible après réussite du quiz final à 70&nbsp;%.</p>',
        'introformat' => FORMAT_HTML,
        'requiredtime' => 0,
        'verifyany' => 1,
        'deliveryoption' => 'D',
        'usecustomfilename' => 1,
        'customfilenamepattern' => 'Certificat-IA-{FULLNAME}',
        'emailstudents' => 0,
        'emailteachers' => 0,
        'emailothers' => '',
        'protection_print' => 0,
        'protection_modify' => 1,
        'protection_copy' => 1,
        'language' => 'fr',
        'visible' => 1,
        'completion' => COMPLETION_TRACKING_AUTOMATIC,
        'completionview' => COMPLETION_VIEW_REQUIRED,
    ];
    $certcm = add_moduleinfo($certmoduleinfo, $course, null);
    $certificate = $DB->get_record('customcert', ['id' => $certcm->instance], '*', MUST_EXIST);
    $page = $DB->get_record('customcert_pages', ['templateid' => $certificate->templateid], '*', MUST_EXIST);
    $DB->set_field('customcert_pages', 'width', 297, ['id' => $page->id]);
    $DB->set_field('customcert_pages', 'height', 210, ['id' => $page->id]);
    $now = time();
    $elements = [
        ['Institut de Santé Publique', 'text', ['text' => '<strong>INSTITUT DE SANTÉ PUBLIQUE</strong>', 'font' => 'helvetica', 'fontsize' => 20, 'colour' => '#005489', 'width' => 250], 148, 38, 'C'],
        ['Titre du certificat', 'text', ['text' => '<strong>CERTIFICAT DE RÉUSSITE</strong>', 'font' => 'helvetica', 'fontsize' => 30, 'colour' => '#008F87', 'width' => 250], 148, 70, 'C'],
        ['Mention attribué à', 'text', ['text' => 'Ce certificat est décerné à', 'font' => 'helvetica', 'fontsize' => 13, 'colour' => '#344054', 'width' => 230], 148, 96, 'C'],
        ['Nom de l’apprenant', 'studentname', ['font' => 'helvetica', 'fontsize' => 24, 'colour' => '#102A43', 'width' => 250], 148, 112, 'C'],
        ['Mention cours', 'text', ['text' => 'pour avoir satisfait aux exigences du cours', 'font' => 'helvetica', 'fontsize' => 13, 'colour' => '#344054', 'width' => 230], 148, 132, 'C'],
        ['Nom du cours', 'coursename', ['font' => 'helvetica', 'fontsize' => 18, 'colour' => '#005489', 'width' => 250], 148, 148, 'C'],
        ['Date', 'date', ['dateitem' => '2', 'dateformat' => '1', 'font' => 'helvetica', 'fontsize' => 11, 'colour' => '#475467', 'width' => 90], 68, 181, 'C'],
        ['Code de vérification', 'code', ['font' => 'helvetica', 'fontsize' => 10, 'colour' => '#475467', 'width' => 100], 228, 181, 'C'],
    ];
    $sequence = 1;
    foreach ($elements as [$name, $type, $data, $x, $y, $alignment]) {
        $DB->insert_record('customcert_elements', (object) [
            'pageid' => $page->id,
            'name' => $name,
            'element' => $type,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'posx' => $x,
            'posy' => $y,
            'refpoint' => 1,
            'alignment' => $alignment,
            'sequence' => $sequence++,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
    $aimessages[] = 'Certificat final IA créé.';
}
$certificatecm = get_coursemodule_from_instance('customcert', $certificate->id, $course->id, false, MUST_EXIST);
$availability = json_encode([
    'op' => '&',
    'showc' => [true],
    'c' => [[
        'type' => 'completion',
        'cm' => $quizcm->id,
        'e' => COMPLETION_COMPLETE_PASS,
    ]],
]);
if ($certificatecm->availability !== $availability) {
    $DB->set_field('course_modules', 'availability', $availability, ['id' => $certificatecm->id]);
}

// Enable no-key self-enrolment so a learner can test the registration step.
$studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
$selfplugin = enrol_get_plugin('self');
$selfinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self']);
if (!$selfinstance) {
    $selfid = $selfplugin->add_instance($course, [
        'status' => ENROL_INSTANCE_ENABLED,
        'name' => 'Inscription libre — parcours IA',
        'roleid' => $studentrole->id,
        'enrolperiod' => 0,
        'expirynotify' => 0,
        'expirythreshold' => DAYSECS,
        'notifyall' => 0,
        'customint1' => 0,
        'customint2' => 0,
        'customint3' => 0,
        'customint4' => ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
        'customint5' => 0,
        'customint6' => 1,
        'password' => '',
        'customtext1' => '<p>Bienvenue dans le parcours IA. Aucune clé d’inscription n’est requise.</p>',
    ]);
    $selfinstance = $DB->get_record('enrol', ['id' => $selfid], '*', MUST_EXIST);
    $aimessages[] = 'Auto-inscription étudiante activée sans clé.';
}
if ($selfinstance && ((int) $selfinstance->status !== ENROL_INSTANCE_ENABLED || $selfinstance->password !== '')) {
    $selfinstance->status = ENROL_INSTANCE_ENABLED;
    $selfinstance->password = '';
    $selfinstance->customint1 = 0;
    $selfinstance->customint6 = 1;
    $DB->update_record('enrol', $selfinstance);
}

// Dedicated account with a known password; it is intentionally not pre-enrolled,
// so the user can test the complete sign-up step from the learner perspective.
$testusername = 'student.ia.test';
$testpassword = 'StudentIA123!';
$teststudent = $DB->get_record('user', ['username' => $testusername, 'mnethostid' => $CFG->mnet_localhost_id]);
if (!$teststudent) {
    $teststudent = (object) [
        'auth' => 'manual',
        'username' => $testusername,
        'password' => $testpassword,
        'firstname' => 'Étudiant',
        'lastname' => 'Test IA',
        'email' => 'student.ia.test@example.test',
        'lang' => 'fr',
        'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
        'maildisplay' => 2,
        'city' => 'Niamey',
        'country' => 'NE',
    ];
    $teststudent->id = user_create_user($teststudent, true, true);
    $aimessages[] = 'Compte étudiant de test créé : ' . $testusername . '.';
}

rebuild_course_cache($course->id, true);
purge_all_caches();

echo "Cours IA configuré : {$course->fullname} (id {$course->id})\n";
echo "URL : {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
echo "Compte de test : {$testusername} / {$testpassword}\n";
foreach ($aimessages as $message) {
    echo '- ' . $message . "\n";
}
