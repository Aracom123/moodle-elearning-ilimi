<?php
/**
 * Script v2 — Insertion contenu cours SCOM101
 * Utilise course_module_create() pour éviter les erreurs DB
 */

require_once('../config.php');
require_once($CFG->libdir  . '/accesslib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');

require_login();
if (!is_siteadmin()) die('Accès refusé.');

echo "<style>
body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 20px;background:#f4f7fa}
h2{color:#1F3A5F;border-bottom:3px solid #0D7377;padding-bottom:8px}
h3{color:#0D7377;background:#e8f5f5;padding:8px 12px;border-left:4px solid #0D7377;margin-top:25px}
ul{list-style:none;padding:0}
li{padding:6px 10px;border-bottom:1px solid #eee;background:white;margin-bottom:3px;border-radius:3px}
.ok{color:#2E7D32;font-weight:bold}
.err{color:#c0392b;font-weight:bold}
.box{background:white;border-left:4px solid #0D7377;padding:15px 20px;margin-top:20px;border-radius:4px}
a.btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#1F3A5F;color:white;text-decoration:none;border-radius:4px}
</style>";

echo "<h2>📚 Insertion du contenu — Principes de Santé Communautaire (v2)</h2>";

// ── Récupérer le cours ───────────────────────────────────────────────
$course = $DB->get_record('course', ['shortname' => 'SCOM101']);
if (!$course) die("<p style='color:red'>❌ Cours SCOM101 introuvable.</p>");
echo "<p>✅ Cours : <strong>{$course->fullname}</strong> (ID: {$course->id})</p>";

$created = 0;
$errors  = 0;

// ── Mettre à jour le nombre de sections ─────────────────────────────
$DB->set_field('course', 'numsections', 4, ['id' => $course->id]);

// ── Titres des sections ──────────────────────────────────────────────
$section_titles = [
    0 => 'Informations générales',
    1 => 'Section 1 — Introduction à la Santé Communautaire',
    2 => 'Section 2 — Diagnostic Communautaire',
    3 => 'Section 3 — Planification et Intervention',
    4 => 'Section 4 — Évaluation des Programmes',
];
foreach ($section_titles as $num => $title) {
    course_create_sections_if_missing($course, $num);
    $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num]);
    if ($sec) $DB->set_field('course_sections', 'name', $title, ['id' => $sec->id]);
}

// ====================================================================
// Fonction utilitaire : ajouter une PAGE
// ====================================================================
function add_page($course, $section, $name, $content) {
    global $DB, $created, $errors;
    try {
        course_create_sections_if_missing($course, $section);
        $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $section]);

        $mod = $DB->get_record('modules', ['name' => 'page'], '*', MUST_EXIST);

        $page = new stdClass();
        $page->course        = $course->id;
        $page->name          = $name;
        $page->intro         = '';
        $page->introformat   = FORMAT_HTML;
        $page->content       = $content;
        $page->contentformat = FORMAT_HTML;
        $page->display       = 5;
        $page->displayoptions = serialize(['printheading'=>1,'printintro'=>0]);
        $page->timemodified  = time();
        $page->id = $DB->insert_record('page', $page);

        $cm = new stdClass();
        $cm->course   = $course->id;
        $cm->module   = $mod->id;
        $cm->instance = $page->id;
        $cm->section  = $sec->id;
        $cm->visible  = 1;
        $cm->visibleold = 1;
        $cm->added    = time();
        $cm->id = $DB->insert_record('course_modules', $cm);

        $seq = trim($sec->sequence ?? '');
        $seq = $seq ? $seq . ',' . $cm->id : (string)$cm->id;
        $DB->set_field('course_sections', 'sequence', $seq, ['id' => $sec->id]);

        $created++;
        echo "<li><span class='ok'>✅</span> Page : <strong>$name</strong></li>";
    } catch (Exception $e) {
        $errors++;
        echo "<li><span class='err'>❌ Erreur Page '$name' : " . $e->getMessage() . "</span></li>";
    }
}

// ====================================================================
// Fonction utilitaire : ajouter un FORUM
// ====================================================================
function add_forum($course, $section, $name, $intro) {
    global $DB, $created, $errors;
    try {
        course_create_sections_if_missing($course, $section);
        $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $section]);
        $mod = $DB->get_record('modules', ['name' => 'forum'], '*', MUST_EXIST);

        $forum = new stdClass();
        $forum->course       = $course->id;
        $forum->type         = 'general';
        $forum->name         = $name;
        $forum->intro        = $intro;
        $forum->introformat  = FORMAT_HTML;
        $forum->assessed     = 0;
        $forum->scale        = 0;
        $forum->maxbytes     = 0;
        $forum->maxattachments = 9;
        $forum->forcesubscribe = 0;
        $forum->trackingtype = 1;
        $forum->rsstype      = 0;
        $forum->rssarticles  = 0;
        $forum->timemodified = time();
        $forum->warnafter    = 0;
        $forum->blockafter   = 0;
        $forum->blockperiod  = 0;
        $forum->completiondiscussions = 0;
        $forum->completionreplies = 0;
        $forum->completionposts = 0;
        $forum->displaywordcount = 0;
        $forum->id = $DB->insert_record('forum', $forum);

        $cm = new stdClass();
        $cm->course   = $course->id;
        $cm->module   = $mod->id;
        $cm->instance = $forum->id;
        $cm->section  = $sec->id;
        $cm->visible  = 1;
        $cm->visibleold = 1;
        $cm->added    = time();
        $cm->id = $DB->insert_record('course_modules', $cm);

        $seq = trim($sec->sequence ?? '');
        $seq = $seq ? $seq . ',' . $cm->id : (string)$cm->id;
        $DB->set_field('course_sections', 'sequence', $seq, ['id' => $sec->id]);

        $created++;
        echo "<li><span class='ok'>✅</span> Forum : <strong>$name</strong></li>";
    } catch (Exception $e) {
        $errors++;
        echo "<li><span class='err'>❌ Erreur Forum '$name' : " . $e->getMessage() . "</span></li>";
    }
}

// ====================================================================
// Fonction utilitaire : ajouter un DEVOIR (assign)
// ====================================================================
function add_assign($course, $section, $name, $intro, $grade = 20, $days = 7) {
    global $DB, $created, $errors;
    try {
        course_create_sections_if_missing($course, $section);
        $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $section]);
        $mod = $DB->get_record('modules', ['name' => 'assign'], '*', MUST_EXIST);

        $assign = new stdClass();
        $assign->course      = $course->id;
        $assign->name        = $name;
        $assign->intro       = $intro;
        $assign->introformat = FORMAT_HTML;
        $assign->alwaysshowdescription    = 1;
        $assign->nosubmissions            = 0;
        $assign->submissiondrafts         = 0;
        $assign->sendnotifications        = 0;
        $assign->sendlatenotifications    = 0;
        $assign->sendstudentnotifications = 1;
        $assign->duedate                  = time() + ($days * 24 * 3600);
        $assign->allowsubmissionsfromdate = time();
        $assign->grade                    = $grade;
        $assign->timemodified             = time();
        $assign->requiresubmissionstatement = 0;
        $assign->completionsubmit         = 0;
        $assign->cutoffdate               = 0;
        $assign->gradingduedate           = 0;
        $assign->teamsubmission           = 0;
        $assign->requireallteammemberssubmit = 0;
        $assign->teamsubmissiongroupingid = 0;
        $assign->blindmarking             = 0;
        $assign->hidegrader               = 0;
        $assign->revealidentities         = 0;
        $assign->attemptreopenmethod      = 'none';
        $assign->maxattempts              = -1;
        $assign->markingworkflow          = 0;
        $assign->markingallocation        = 0;
        $assign->id = $DB->insert_record('assign', $assign);

        // Plugin soumission texte en ligne
        $sub = new stdClass();
        $sub->assignment = $assign->id;
        $sub->plugin     = 'onlinetext';
        $sub->subtype    = 'assignsubmission';
        $sub->name       = 'enabled';
        $sub->value      = '1';
        $DB->insert_record('assign_plugin_config', $sub);

        // Plugin soumission fichier
        $sub2 = new stdClass();
        $sub2->assignment = $assign->id;
        $sub2->plugin     = 'file';
        $sub2->subtype    = 'assignsubmission';
        $sub2->name       = 'enabled';
        $sub2->value      = '1';
        $DB->insert_record('assign_plugin_config', $sub2);

        $cm = new stdClass();
        $cm->course   = $course->id;
        $cm->module   = $mod->id;
        $cm->instance = $assign->id;
        $cm->section  = $sec->id;
        $cm->visible  = 1;
        $cm->visibleold = 1;
        $cm->added    = time();
        $cm->id = $DB->insert_record('course_modules', $cm);

        $seq = trim($sec->sequence ?? '');
        $seq = $seq ? $seq . ',' . $cm->id : (string)$cm->id;
        $DB->set_field('course_sections', 'sequence', $seq, ['id' => $sec->id]);

        $created++;
        echo "<li><span class='ok'>✅</span> Devoir : <strong>$name</strong> (/{$grade} pts, délai {$days}j)</li>";
    } catch (Exception $e) {
        $errors++;
        echo "<li><span class='err'>❌ Erreur Devoir '$name' : " . $e->getMessage() . "</span></li>";
    }
}

// ====================================================================
// Fonction utilitaire : ajouter un URL (vidéo)
// ====================================================================
function add_url($course, $section, $name, $url, $intro = '') {
    global $DB, $created, $errors;
    try {
        course_create_sections_if_missing($course, $section);
        $sec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $section]);
        $mod = $DB->get_record('modules', ['name' => 'url'], '*', MUST_EXIST);

        $urlmod = new stdClass();
        $urlmod->course      = $course->id;
        $urlmod->name        = $name;
        $urlmod->intro       = $intro;
        $urlmod->introformat = FORMAT_HTML;
        $urlmod->externalurl = $url;
        $urlmod->display     = 0;
        $urlmod->displayoptions = serialize(['printintro'=>1]);
        $urlmod->parameters  = '';
        $urlmod->timemodified = time();
        $urlmod->id = $DB->insert_record('url', $urlmod);

        $cm = new stdClass();
        $cm->course   = $course->id;
        $cm->module   = $mod->id;
        $cm->instance = $urlmod->id;
        $cm->section  = $sec->id;
        $cm->visible  = 1;
        $cm->visibleold = 1;
        $cm->added    = time();
        $cm->id = $DB->insert_record('course_modules', $cm);

        $seq = trim($sec->sequence ?? '');
        $seq = $seq ? $seq . ',' . $cm->id : (string)$cm->id;
        $DB->set_field('course_sections', 'sequence', $seq, ['id' => $sec->id]);

        $created++;
        echo "<li><span class='ok'>✅</span> Lien : <strong>$name</strong></li>";
    } catch (Exception $e) {
        $errors++;
        echo "<li><span class='err'>❌ Erreur URL '$name' : " . $e->getMessage() . "</span></li>";
    }
}

// ====================================================================
// SECTION 0 — Informations générales
// ====================================================================
echo "<h3>📋 Section 0 — Informations générales</h3><ul>";

add_page($course, 0, 'Bienvenue dans ce cours', '
<h2>Bienvenue dans le cours — Principes de Santé Communautaire</h2>
<p>Ce cours vous introduit aux fondements de la santé communautaire, discipline essentielle pour tout professionnel de santé publique.</p>
<h3>Objectifs pédagogiques</h3>
<ul>
  <li>Comprendre les concepts fondamentaux de la santé communautaire</li>
  <li>Maîtriser les méthodes de diagnostic communautaire</li>
  <li>Concevoir et planifier un programme d\'intervention en santé</li>
  <li>Évaluer l\'impact d\'un programme de santé communautaire</li>
</ul>
<h3>Organisation</h3>
<p>Le cours est organisé en <strong>4 sections thématiques</strong>, chacune comportant des ressources, des activités pratiques et une évaluation.</p>
<p><strong>Enseignant :</strong> Dr. Amadou Koné — <a href="mailto:a.kone@ilimi.edu">a.kone@ilimi.edu</a></p>');

add_page($course, 0, 'Plan du cours et objectifs pédagogiques', '
<h2>Plan du cours — SCOM101</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
  <tr style="background:#1F3A5F;color:white"><th>Section</th><th>Thème</th><th>Durée</th><th>Évaluation</th></tr>
  <tr><td>1</td><td>Introduction à la Santé Communautaire</td><td>3h</td><td>Quiz (20 min)</td></tr>
  <tr><td>2</td><td>Diagnostic Communautaire</td><td>4h</td><td>Étude de cas + Devoir /20</td></tr>
  <tr><td>3</td><td>Planification et Intervention</td><td>4h</td><td>Forum + Quiz (30 min)</td></tr>
  <tr><td>4</td><td>Évaluation des Programmes</td><td>3h</td><td>Devoir final /20</td></tr>
</table>
<h3>Modalités d\'évaluation</h3>
<ul>
  <li>Quiz formatifs : 20%</li>
  <li>Devoirs pratiques : 40%</li>
  <li>Devoir final : 40%</li>
</ul>
<h3>Bibliographie</h3>
<ul>
  <li>OMS (2020). <em>Renforcement des systèmes de santé communautaires.</em> Genève.</li>
  <li>Vlassoff, C. (2019). <em>Santé communautaire en Afrique subsaharienne.</em> L\'Harmattan.</li>
  <li>Ministère de la Santé (2022). <em>Guide national de santé communautaire.</em> Conakry.</li>
</ul>');

echo "</ul>";

// ====================================================================
// SECTION 1 — Introduction
// ====================================================================
echo "<h3>📚 Section 1 — Introduction à la Santé Communautaire</h3><ul>";

add_page($course, 1, 'Définitions et concepts fondamentaux', '
<h2>Définitions et concepts fondamentaux</h2>
<h3>1. Qu\'est-ce que la santé communautaire ?</h3>
<p>La <strong>santé communautaire</strong> est une branche de la santé publique qui s\'intéresse à la santé des populations dans leur contexte social, environnemental et culturel.</p>
<blockquote style="border-left:4px solid #0D7377;padding:10px 20px;background:#f0f8f8;margin:15px 0">
  <em>"La santé est un état de complet bien-être physique, mental et social."</em> — OMS, 1946
</blockquote>
<h3>2. Déterminants sociaux de la santé</h3>
<ul>
  <li>Revenu et statut social</li>
  <li>Éducation et alphabétisation</li>
  <li>Environnement physique</li>
  <li>Réseaux de soutien social</li>
  <li>Accès aux services de santé</li>
</ul>
<h3>3. Approche biomédicale vs communautaire</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
  <tr style="background:#1F3A5F;color:white"><th>Approche biomédicale</th><th>Approche communautaire</th></tr>
  <tr><td>Centrée sur la maladie</td><td>Centrée sur la santé</td></tr>
  <tr><td>Individu isolé</td><td>Individu dans son milieu</td></tr>
  <tr><td>Curative</td><td>Préventive et promotionnelle</td></tr>
</table>');

add_url($course, 1, 'Vidéo — Introduction à la santé communautaire (OMS)',
    'https://www.youtube.com/watch?v=Sb5PpIbYIXU',
    '<p>Regardez cette vidéo introductive sur les principes de la santé communautaire.</p>');

add_page($course, 1, 'Quiz 1 — Questions de révision (Section 1)', '
<h2>Questions de révision — Section 1</h2>
<p><em>Réfléchissez à ces questions avant de passer au Quiz noté :</em></p>
<ol>
  <li>Quelle est la différence entre santé publique et santé communautaire ?</li>
  <li>Citez 3 déterminants sociaux de la santé et expliquez leur impact.</li>
  <li>Pourquoi l\'approche participative est-elle essentielle en santé communautaire ?</li>
  <li>Comment mesure-t-on l\'état de santé d\'une communauté ?</li>
  <li>Quels sont les acteurs clés d\'une intervention communautaire en santé ?</li>
</ol>
<div style="background:#e8f5f5;padding:15px;border-radius:4px;margin-top:15px">
  <strong>💡 Conseil :</strong> Notez vos réponses avant de les comparer avec le cours. Ces questions seront similaires à celles du quiz noté.
</div>');

echo "</ul>";

// ====================================================================
// SECTION 2 — Diagnostic Communautaire
// ====================================================================
echo "<h3>📚 Section 2 — Diagnostic Communautaire</h3><ul>";

add_page($course, 2, 'Méthodes de collecte de données communautaires', '
<h2>Méthodes de collecte de données communautaires</h2>
<h3>1. Méthodes quantitatives</h3>
<ul>
  <li><strong>Enquêtes par questionnaire</strong> : données standardisées sur échantillon représentatif</li>
  <li><strong>Revue des registres de santé</strong> : analyse des données existantes</li>
  <li><strong>Recensement</strong> : dénombrement exhaustif de la population</li>
</ul>
<h3>2. Méthodes qualitatives</h3>
<ul>
  <li><strong>Entretiens individuels</strong> : recueil des perceptions et expériences</li>
  <li><strong>Focus groupes</strong> : discussion collective sur les représentations</li>
  <li><strong>Observation participante</strong> : immersion dans la communauté</li>
</ul>
<h3>3. Méthodes participatives (MARP)</h3>
<ul>
  <li>Cartographie communautaire</li>
  <li>Calendrier saisonnier</li>
  <li>Arbre à problèmes</li>
  <li>Classement par richesse</li>
</ul>
<h3>4. Indicateurs clés</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
  <tr style="background:#1F3A5F;color:white"><th>Domaine</th><th>Indicateurs</th></tr>
  <tr><td>Démographie</td><td>Structure par âge, taux natalité/mortalité</td></tr>
  <tr><td>Santé</td><td>Morbidité, mortalité infantile, couverture vaccinale</td></tr>
  <tr><td>Nutrition</td><td>Prévalence malnutrition, allaitement maternel</td></tr>
  <tr><td>Eau/Assainissement</td><td>Accès eau potable, latrines, hygiène</td></tr>
</table>');

add_page($course, 2, 'Étude de cas — Village de Kourouma (Guinée)', '
<h2>Étude de cas : Village de Kourouma — Guinée</h2>
<div style="background:#fff3cd;border:1px solid #ffc107;padding:15px;border-radius:4px;margin-bottom:20px">
  <strong>📍 Contexte :</strong> Le village de Kourouma compte 2 500 habitants dans la région de Kindia. Le taux de mortalité infantile y est de 85 pour 1000, contre 55 pour 1000 au niveau national.
</div>
<h3>Données disponibles</h3>
<ul>
  <li>Accès à l\'eau potable : <strong>40%</strong> des ménages</li>
  <li>Couverture vaccinale : <strong>52%</strong> (objectif : 80%)</li>
  <li>Accouchements assistés : <strong>35%</strong></li>
  <li>Malnutrition infantile : <strong>28%</strong></li>
  <li>Distance au centre de santé : <strong>12 km</strong></li>
  <li>Taux d\'alphabétisation : <strong>31%</strong></li>
</ul>
<h3>Questions d\'analyse</h3>
<ol>
  <li>Quels sont les principaux problèmes de santé identifiés ?</li>
  <li>Quels déterminants sociaux de la santé sont en jeu ?</li>
  <li>Quelle méthode de collecte supplémentaire recommanderiez-vous ?</li>
  <li>Proposez deux priorités d\'intervention justifiées.</li>
</ol>');

add_assign($course, 2, 'Devoir 1 — Diagnostic communautaire simplifié',
'<h3>Consignes</h3>
<p>À partir de l\'étude de cas de Kourouma, rédigez un <strong>diagnostic communautaire simplifié</strong> (2-3 pages) :</p>
<ol>
  <li>Présentation de la situation sanitaire (2 pts)</li>
  <li>Identification des problèmes prioritaires (3 pts)</li>
  <li>Analyse des déterminants et facteurs de risque (3 pts)</li>
  <li>Recommandations d\'intervention (2 pts)</li>
</ol>
<p><strong>Format :</strong> Word ou PDF | <strong>Note :</strong> /10</p>', 10, 7);

echo "</ul>";

// ====================================================================
// SECTION 3 — Planification et Intervention
// ====================================================================
echo "<h3>📚 Section 3 — Planification et Intervention</h3><ul>";

add_page($course, 3, 'Les étapes d\'un programme de santé communautaire', '
<h2>Les étapes d\'un programme de santé communautaire</h2>
<div style="background:#e8f5f5;padding:20px;border-radius:8px;margin:15px 0">
  <ol style="font-size:16px;line-height:2">
    <li><strong>🔍 Analyse de la situation</strong> — Diagnostic communautaire</li>
    <li><strong>🎯 Définition des priorités</strong> — Choix des problèmes à résoudre</li>
    <li><strong>📋 Formulation du programme</strong> — Objectifs, activités, ressources</li>
    <li><strong>⚙️ Mise en œuvre</strong> — Exécution des activités</li>
    <li><strong>📊 Suivi et évaluation</strong> — Mesure des résultats</li>
  </ol>
</div>
<h3>Cadre logique</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
  <tr style="background:#1F3A5F;color:white"><th>Niveau</th><th>Indicateurs</th><th>Sources</th><th>Hypothèses</th></tr>
  <tr><td>Objectif global</td><td>Réduire mortalité infantile de 20%</td><td>Statistiques sanitaires</td><td>Engagement politique</td></tr>
  <tr><td>Objectif spécifique</td><td>Couverture vaccinale à 80%</td><td>Registres vaccination</td><td>Disponibilité vaccins</td></tr>
  <tr><td>Résultats</td><td>500 enfants vaccinés/trimestre</td><td>Fiches vaccination</td><td>Acceptation communauté</td></tr>
  <tr><td>Activités</td><td>12 séances mobiles/an</td><td>Rapports d\'activité</td><td>Personnel disponible</td></tr>
</table>
<h3>Analyse SWOT</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
  <tr>
    <td style="background:#d4edda"><strong>Forces ✅</strong><br>Agents communautaires actifs<br>Leaders engagés</td>
    <td style="background:#f8d7da"><strong>Faiblesses ⚠️</strong><br>Ressources limitées<br>Accès géographique difficile</td>
  </tr>
  <tr>
    <td style="background:#cce5ff"><strong>Opportunités 🚀</strong><br>Financement ONG<br>Téléphonie mobile répandue</td>
    <td style="background:#fff3cd"><strong>Menaces 🔴</strong><br>Croyances traditionnelles<br>Mobilité de la population</td>
  </tr>
</table>');

add_forum($course, 3,
    'Forum — Obstacles à l\'intervention communautaire',
    '<p>Partagez vos réflexions :</p>
    <blockquote style="border-left:4px solid #0D7377;padding:10px 20px;background:#f0f8f8;font-style:italic">
    "Dans votre contexte, quels sont les principaux obstacles à la mise en œuvre d\'un programme de santé communautaire ? Comment les surmonter ?"
    </blockquote>
    <p>Publiez <strong>au moins une contribution</strong> et répondez à <strong>deux</strong> interventions de collègues.</p>');

add_page($course, 3, 'Quiz 2 — Questions de révision (Section 3)', '
<h2>Questions de révision — Section 3</h2>
<ol>
  <li>Quelles sont les 5 étapes du cycle de planification en santé ?</li>
  <li>Qu\'est-ce qu\'un cadre logique ? À quoi sert-il ?</li>
  <li>Comment prioriser les problèmes de santé dans une communauté ?</li>
  <li>Quelle est la différence entre efficacité et efficience d\'un programme ?</li>
  <li>Donnez un exemple d\'objectif SMART en santé communautaire.</li>
</ol>');

echo "</ul>";

// ====================================================================
// SECTION 4 — Évaluation des Programmes
// ====================================================================
echo "<h3>📚 Section 4 — Évaluation des Programmes</h3><ul>";

add_page($course, 4, 'Indicateurs de suivi et évaluation', '
<h2>Indicateurs de suivi et évaluation</h2>
<h3>1. Types d\'indicateurs</h3>
<ul>
  <li><strong>Intrants</strong> : ressources mobilisées (budget, personnel, matériel)</li>
  <li><strong>Processus</strong> : activités réalisées (séances, personnes touchées)</li>
  <li><strong>Résultats</strong> : changements à court terme (connaissances, attitudes)</li>
  <li><strong>Impact</strong> : changements à long terme (mortalité, morbidité)</li>
</ul>
<h3>2. Critères SMART</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">
  <tr style="background:#1F3A5F;color:white"><th>Critère</th><th>Définition</th><th>Exemple</th></tr>
  <tr><td><strong>S</strong>pécifique</td><td>Mesure un phénomène précis</td><td>Couverture vaccinale rougeole &lt;5 ans</td></tr>
  <tr><td><strong>M</strong>esurable</td><td>Quantifiable objectivement</td><td>Exprimé en pourcentage</td></tr>
  <tr><td><strong>A</strong>tteignable</td><td>Réaliste dans le contexte</td><td>Passer de 52% à 75% en 12 mois</td></tr>
  <tr><td><strong>R</strong>elevant</td><td>Pertinent par rapport à l\'objectif</td><td>Lié à la réduction mortalité infantile</td></tr>
  <tr><td><strong>T</strong>emporel</td><td>Défini dans le temps</td><td>Mesuré à 6 et 12 mois</td></tr>
</table>
<h3>3. Questions clés de l\'évaluation</h3>
<ul>
  <li><strong>Pertinence</strong> : Le programme répond-il aux besoins ?</li>
  <li><strong>Efficacité</strong> : Les objectifs ont-ils été atteints ?</li>
  <li><strong>Efficience</strong> : Les résultats justifient-ils les ressources ?</li>
  <li><strong>Durabilité</strong> : Les effets se maintiendront-ils ?</li>
</ul>');

add_assign($course, 4, 'Devoir final — Concevoir un mini-programme d\'intervention',
'<h3>📝 Travail Final — Noté sur 20 points</h3>
<p>Concevez un <strong>mini-programme d\'intervention en santé communautaire</strong> pour le village de Kourouma.</p>
<h4>Structure attendue :</h4>
<ol>
  <li>Résumé du diagnostic (2 pts)</li>
  <li>Problème prioritaire et justification (3 pts)</li>
  <li>Objectifs SMART (3 pts)</li>
  <li>Plan d\'activités avec calendrier (4 pts)</li>
  <li>Budget prévisionnel simplifié (3 pts)</li>
  <li>Indicateurs de suivi et évaluation (3 pts)</li>
  <li>Qualité de rédaction (2 pts)</li>
</ol>
<p><strong>Format :</strong> 5 à 8 pages — Word ou PDF<br>
<strong>Note maximale :</strong> 20/20</p>
<div style="background:#fff3cd;border:1px solid #ffc107;padding:10px;border-radius:4px;margin-top:15px">
  ⚠️ Tout plagiat entraîne une note de zéro. Travail strictement individuel.
</div>', 20, 14);

echo "</ul>";

// ── Purger le cache ──────────────────────────────────────────────────
rebuild_course_cache($course->id, true);

// ── Résumé ───────────────────────────────────────────────────────────
echo "<div class='box'>";
echo "<h2>📊 Résumé</h2>";
echo "<p>✅ Activités créées : <strong>$created</strong></p>";
echo "<p>❌ Erreurs : <strong>$errors</strong></p>";
if ($errors === 0) {
    echo "<p style='color:#2E7D32;font-weight:bold'>🎉 Tout le contenu a été inséré avec succès !</p>";
}
echo "</div>";
echo "<a class='btn' href='/course/view.php?id={$course->id}'>👉 Voir le cours</a>";

@unlink(__FILE__);
echo "<p style='color:gray;font-size:12px;margin-top:20px'>Script supprimé pour des raisons de sécurité.</p>";
