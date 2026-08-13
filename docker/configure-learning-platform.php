<?php
// Idempotent local platform configuration for the ISP eLearning project.

define('CLI_SCRIPT', true);

require dirname(__DIR__) . '/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/forum/lib.php';
require_once $CFG->dirroot . '/lib/badgeslib.php';
require_once $CFG->dirroot . '/completion/criteria/completion_criteria_activity.php';
require_once $CFG->dirroot . '/completion/completion_aggregation.php';

use core_badges\badge;
use core_competency\api as competency_api;
use core_competency\competency;
use core_competency\competency_framework;
use core_competency\template as competency_template;
use core_reportbuilder\local\helpers\report as report_helper;

global $DB, $CFG, $USER;

$admin = get_admin();
\core\session\manager::set_user($admin);

$messages = [];
$systemcontext = context_system::instance();

// Platform-wide switches and sensible defaults for new courses.
foreach ([
    'enableanalytics' => 1,
    'enablestats' => 1,
    'enablecompletion' => 1,
    'enablebadges' => 1,
    'enablecustomreports' => 1,
    'badges_allowcoursebadges' => 1,
] as $name => $value) {
    set_config($name, $value);
}

// Configure Custom certificate (Moodle Marketplace plugin 1466) for the local
// learner journey and certificate verification workflow.
if ((int) get_config('mod_customcert', 'version') >= 2026042006) {
    foreach ([
        'verifyallcertificates' => 1,
        'verifyany' => 1,
        'showposxy' => 1,
        'useadhoc' => 1,
        'returncourse' => 1,
        'emailstudents' => 0,
        'emailteachers' => 0,
        'requiredtime' => 0,
        'protection_print' => 0,
        'protection_modify' => 1,
        'protection_copy' => 1,
        'codegenerationmethod' => 0,
    ] as $name => $value) {
        set_config($name, $value, 'customcert');
    }
    $messages[] = 'Custom certificate 5.2.5 configuré pour les certificats vérifiables.';
}

set_config('enabled', 1, 'core_competency');
set_config('enablecompletion', 1, 'moodlecourse');
set_config('showcompletionconditions', 1, 'moodlecourse');
set_config('showreports', 1, 'moodlecourse');
set_config('badges_defaultissuername', 'Institut de Santé Publique');
set_config('badges_defaultissuercontact', 'contact@beit.ne');

// Use the official Moove theme with the ISP visual identity.
set_config('theme', 'moove');
foreach ([
    // Colours used by the ISP public website.
    'brandcolor' => '#6E3A41',
    'secondarymenucolor' => '#2A69B8',
    'fontsite' => 'Inter',
    'enablecourseindex' => 1,
    'enableclassicbreadcrumb' => 0,
    'enabledarkmode' => 0,
    'slidercount' => 0,
    'displaymarketingbox' => 0,
    'numbersfrontpage' => 0,
    'faqcount' => 0,
] as $name => $value) {
    set_config($name, $value, 'theme_moove');
}

// Keep the official ISP assets in Moodle's managed file storage.  The source
// files are versioned with this local project, so the configuration is repeatable.
$storethemefile = static function(
    string $sourcepath,
    string $component,
    string $filearea,
    string $settingplugin,
    string $settingname,
    string $filename
) use ($systemcontext, $admin): void {
    if (!is_readable($sourcepath)) {
        throw new moodle_exception('missingthemeasset', 'error', '', $sourcepath);
    }

    $fs = get_file_storage();
    $fs->delete_area_files($systemcontext->id, $component, $filearea, 0);
    $fs->create_file_from_pathname((object) [
        'contextid' => $systemcontext->id,
        'component' => $component,
        'filearea' => $filearea,
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename,
        'userid' => $admin->id,
    ], $sourcepath);
    set_config($settingname, '/' . $filename, $settingplugin);
};

$officiallogopath = dirname(__DIR__) . '/Logo-isp-new 1.png';
$loginphotopath = __DIR__ . '/assets/isp-login-students.jpg';

// Moove takes precedence for the navigation and login pages; core_admin keeps
// the same logo for Moodle areas that do not use the active theme.
$storethemefile($officiallogopath, 'theme_moove', 'logo', 'theme_moove', 'logo', 'isp-niger-logo.png');
$storethemefile($officiallogopath, 'core_admin', 'logo', 'core_admin', 'logo', 'isp-niger-logo.png');
$storethemefile($loginphotopath, 'theme_moove', 'loginbgimg', 'theme_moove', 'loginbgimg', 'isp-niger-students.jpg');

set_config('scss', <<<'SCSS'
// ISP Niger: preserve the original classroom image while ensuring the login
// welcome copy remains legible on all screen sizes.
body.pagelayout-login .login-layout-left-content {
    background-color: rgba(110, 58, 65, 0.84);
    border-left: 4px solid #2A69B8;
    box-shadow: 0 1rem 3rem rgba(110, 58, 65, 0.28);
}

body.pagelayout-login .login-layout-right {
    background-color: #f0f4fb;
}

body.pagelayout-login .login-layout-right .login-container {
    background-color: #fff;
    border-radius: .75rem;
    padding: 2rem;
    box-shadow: 0 .5rem 1.5rem rgba(110, 58, 65, 0.12);
}
SCSS, 'theme_moove');

set_config('auth_instructions',
    '<h1 class="h2 mb-3">Institut de Santé Publique</h1>'
    . '<p>Bienvenue sur la plateforme e-learning de l’ISP Niger.</p>'
    . '<p class="mb-0">Accédez à vos cours, suivez votre progression et obtenez vos certificats.</p>'
);
$messages[] = 'Charte ISP appliquée : logo officiel, palette bordeaux et bleu, photo de salle de cours.';

set_config('frontpage', '0,6');
set_config('frontpageloggedin', '0,6');
if ($DB->record_exists('modules', ['name' => 'attendance'])) {
    set_config('resultsperpage', 50, 'attendance');
    set_config('enablecalendar', 1, 'attendance');
    set_config('showsessiondescriptiononreport', 1, 'attendance');
    set_config('studentscanmark', 0, 'attendance');
}

// Ensure the site announcements forum exists and contains a useful first message.
$site = get_site();
$newsforum = forum_get_course_forum($site->id, 'news');
if (!$DB->record_exists('forum_discussions', [
    'forum' => $newsforum->id,
    'name' => 'Bienvenue sur ISP eLearning',
])) {
    $discussion = (object) [
        'course' => $site->id,
        'forum' => $newsforum->id,
        'name' => 'Bienvenue sur ISP eLearning',
        'message' => '<p>Bienvenue sur la plateforme de formation de l’Institut de Santé Publique.</p>'
            . '<p>Retrouvez ici les actualités, vos cours, votre progression et vos attestations.</p>',
        'messageformat' => FORMAT_HTML,
        'messagetrust' => 0,
        'mailnow' => 0,
        'userid' => $admin->id,
        'groupid' => -1,
        'assessed' => 0,
        'timestart' => 0,
        'timeend' => 0,
    ];
    forum_add_discussion($discussion, null, null, $admin->id);
    $messages[] = 'Annonce d’accueil créée.';
}

// Create a reusable public-health competency framework and learning-plan template.
$frameworkrecord = $DB->get_record('competency_framework', ['idnumber' => 'ISP-SP-FONDAMENTAUX']);
if ($frameworkrecord) {
    $framework = new competency_framework($frameworkrecord->id);
} else {
    $scalerecord = $DB->get_record('scale', ['name' => 'Default competence scale']);
    if (!$scalerecord) {
        $scalerecord = $DB->get_record('scale', [], 'id,name,scale', MUST_EXIST);
    }
    $scaleconfig = [
        ['scaleid' => (string) $scalerecord->id],
        ['name' => 'Not yet competent', 'id' => 1, 'scaledefault' => 1, 'proficient' => 0],
        ['name' => 'Competent', 'id' => 2, 'scaledefault' => 0, 'proficient' => 1],
    ];
    $framework = competency_api::create_framework((object) [
        'shortname' => 'Fondamentaux de santé publique',
        'idnumber' => 'ISP-SP-FONDAMENTAUX',
        'description' => '<p>Compétences transversales mobilisées dans les formations de l’ISP.</p>',
        'descriptionformat' => FORMAT_HTML,
        'visible' => 1,
        'scaleid' => $scalerecord->id,
        'scaleconfiguration' => json_encode($scaleconfig),
        'contextid' => $systemcontext->id,
    ]);
    $messages[] = 'Référentiel de compétences créé.';
}

$competencydefinitions = [
    'ISP-SP-01' => 'Analyser la distribution et les déterminants d’un problème de santé',
    'ISP-SP-02' => 'Interpréter des données biostatistiques',
    'ISP-SP-03' => 'Concevoir une intervention de santé communautaire',
    'ISP-SP-04' => 'Comprendre les déterminants humains et comportementaux',
];
$competencies = [];
foreach ($competencydefinitions as $idnumber => $shortname) {
    $record = $DB->get_record('competency', [
        'competencyframeworkid' => $framework->get('id'),
        'idnumber' => $idnumber,
    ]);
    $competencies[$idnumber] = $record
        ? new competency($record->id)
        : competency_api::create_competency((object) [
            'shortname' => $shortname,
            'idnumber' => $idnumber,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'competencyframeworkid' => $framework->get('id'),
            'parentid' => 0,
        ]);
}

$templaterecord = $DB->get_record('competency_template', ['shortname' => 'Parcours — Fondamentaux de santé publique']);
if ($templaterecord) {
    $plantemplate = new competency_template($templaterecord->id);
} else {
    $plantemplate = competency_api::create_template((object) [
        'shortname' => 'Parcours — Fondamentaux de santé publique',
        'description' => '<p>Parcours de référence pour développer les compétences fondamentales en santé publique.</p>',
        'descriptionformat' => FORMAT_HTML,
        'visible' => 1,
        'duedate' => 0,
        'contextid' => $systemcontext->id,
    ]);
    $messages[] = 'Modèle de plan d’apprentissage créé.';
}

foreach ($competencies as $item) {
    if (!$DB->record_exists('competency_templatecomp', [
        'templateid' => $plantemplate->get('id'),
        'competencyid' => $item->get('id'),
    ])) {
        competency_api::add_competency_to_template($plantemplate->get('id'), $item->get('id'));
    }
}

$coursecompetencies = [
    2 => ['ISP-SP-04'],
    3 => ['ISP-SP-01'],
    4 => ['ISP-SP-02'],
    5 => ['ISP-SP-01'],
    6 => ['ISP-SP-03'],
    7 => ['ISP-SP-03'],
    8 => ['ISP-SP-03'],
];
foreach ($coursecompetencies as $courseid => $idnumbers) {
    if (!$DB->record_exists('course', ['id' => $courseid])) {
        continue;
    }
    foreach ($idnumbers as $idnumber) {
        $competencyid = $competencies[$idnumber]->get('id');
        if (!$DB->record_exists('competency_coursecomp', [
            'courseid' => $courseid,
            'competencyid' => $competencyid,
        ])) {
            competency_api::add_competency_to_course($courseid, $competencyid);
        }
    }
}

// The final quiz in course 6 is the authoritative course-completion rule.
$completioncourseid = 6;
$finalquizcmid = 74;
if ($DB->record_exists('course_modules', ['id' => $finalquizcmid, 'course' => $completioncourseid])
        && !$DB->record_exists('course_completion_criteria', [
            'course' => $completioncourseid,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
            'moduleinstance' => $finalquizcmid,
        ])) {
    $criteriondata = (object) [
        'id' => $completioncourseid,
        'criteria_activity' => [$finalquizcmid => 1],
    ];
    $criterion = new completion_criteria_activity();
    $criterion->update_config($criteriondata);

    foreach ([null, COMPLETION_CRITERIA_TYPE_ACTIVITY] as $criteriatype) {
        $aggregation = completion_aggregation::fetch([
            'course' => $completioncourseid,
            'criteriatype' => $criteriatype,
        ]) ?: new completion_aggregation([
            'course' => $completioncourseid,
            'criteriatype' => $criteriatype,
        ], false);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();
    }
    $messages[] = 'Réussite du quiz final reliée à la complétion du cours.';
}

// Attach the learning plan to users who are actually enrolled as students.
$studentrole = $DB->get_record('role', ['shortname' => 'student']);
if ($studentrole) {
    $studentids = $DB->get_fieldset_sql(
        'SELECT DISTINCT ra.userid
           FROM {role_assignments} ra
           JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :courselevel
           JOIN {user} u ON u.id = ra.userid
          WHERE ra.roleid = :roleid AND u.deleted = 0 AND u.suspended = 0',
        ['courselevel' => CONTEXT_COURSE, 'roleid' => $studentrole->id]
    );
    foreach ($studentids as $studentid) {
        competency_api::create_plan_from_template($plantemplate, (int) $studentid);
    }
    $messages[] = count($studentids) . ' plan(s) d’apprentissage vérifié(s).';
}

// Add the completion-progress block to each real course.
foreach (array_keys($coursecompetencies) as $courseid) {
    if (!$DB->record_exists('course', ['id' => $courseid])) {
        continue;
    }
    $context = context_course::instance($courseid);
    if (!$DB->record_exists('block_instances', [
        'blockname' => 'completion_progress',
        'parentcontextid' => $context->id,
    ])) {
        $now = time();
        $DB->insert_record('block_instances', (object) [
            'blockname' => 'completion_progress',
            'parentcontextid' => $context->id,
            'showinsubcontexts' => 0,
            'requiredbytheme' => 0,
            'pagetypepattern' => 'course-view-*',
            'subpagepattern' => null,
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}

// Add a graded attendance register to every real course. Teachers can then
// create sessions using the course's actual timetable without fabricated dates.
$attendancemodule = $DB->get_record('modules', ['name' => 'attendance']);
if ($attendancemodule) {
    $attendancecreated = 0;
    foreach (array_keys($coursecompetencies) as $courseid) {
        if (!$DB->record_exists('course', ['id' => $courseid])
                || $DB->record_exists('attendance', ['course' => $courseid])) {
            continue;
        }
        $course = get_course($courseid);
        $moduleinfo = (object) [
            'course' => $courseid,
            'module' => $attendancemodule->id,
            'modulename' => 'attendance',
            'section' => 0,
            'name' => 'Suivi des présences',
            'intro' => '<p>Registre des présences, retards, absences et absences justifiées du cours.</p>',
            'introformat' => FORMAT_HTML,
            'showdescription' => 1,
            'grade' => 100,
            'gradepass' => 0,
            'subnet' => '',
            'visible' => 1,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionusegrade' => 1,
        ];
        add_moduleinfo($moduleinfo, $course, null);
        $attendancecreated++;
    }
    if ($attendancecreated > 0) {
        $messages[] = $attendancecreated . ' registre(s) de présence créé(s).';
    }
}

// Create an automatic course-completion badge for the community-health course.
$badgecourseid = 6;
$badgename = 'Fondamentaux de santé communautaire';
if ($DB->record_exists('course', ['id' => $badgecourseid])) {
    $badgerecord = $DB->get_record('badge', ['courseid' => $badgecourseid, 'name' => $badgename]);
    if ($badgerecord) {
        $coursebadge = new badge($badgerecord->id);
    } else {
        $coursebadge = badge::create_badge((object) [
            'name' => $badgename,
            'version' => OPEN_BADGES_V2,
            'language' => 'fr',
            'description' => 'Atteste la réussite du cours Principes de Santé Communautaire.',
            'imagecaption' => 'Emblème ISP de réussite en santé communautaire',
            'issuername' => 'Institut de Santé Publique',
            'issuerurl' => $CFG->wwwroot,
            'issuercontact' => 'contact@beit.ne',
            'expiry' => 0,
        ], $badgecourseid);

        $iconfile = make_request_directory() . '/isp-badge.png';
        $image = imagecreatetruecolor(512, 512);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        $blue = imagecolorallocate($image, 0, 84, 137);
        $teal = imagecolorallocate($image, 0, 145, 135);
        $gold = imagecolorallocate($image, 246, 183, 63);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $blue);
        imagefilledellipse($image, 256, 256, 420, 420, $teal);
        imagefilledellipse($image, 256, 256, 330, 330, $blue);
        imagefilledpolygon($image, [256, 105, 286, 220, 401, 220, 307, 286, 343, 397, 256, 329, 169, 397, 205, 286, 111, 220, 226, 220], $gold);
        imagestring($image, 5, 224, 239, 'ISP', $white);
        imagepng($image, $iconfile);
        imagedestroy($image);
        badges_process_badge_image($coursebadge, $iconfile);

        $overall = award_criteria::build([
            'criteriatype' => BADGE_CRITERIA_TYPE_OVERALL,
            'badgeid' => $coursebadge->id,
        ]);
        $overall->save(['agg' => BADGE_CRITERIA_AGGREGATION_ALL]);
        $criterion = award_criteria::build([
            'criteriatype' => BADGE_CRITERIA_TYPE_COURSE,
            'badgeid' => $coursebadge->id,
        ]);
        $criterion->save([
            'agg' => BADGE_CRITERIA_AGGREGATION_ALL,
            'course_' . $badgecourseid => $badgecourseid,
        ]);
        $coursebadge->set_status(BADGE_STATUS_ACTIVE);
        $messages[] = 'Badge automatique de fin de cours créé.';
    }
}

// Add a ready-to-use certificate activity to the same course.
if ($DB->record_exists('course', ['id' => $badgecourseid])
        && !$DB->record_exists('customcert', ['course' => $badgecourseid, 'name' => 'Certificat de réussite ISP'])) {
    $course = get_course($badgecourseid);
    $module = $DB->get_record('modules', ['name' => 'customcert'], '*', MUST_EXIST);
    $moduleinfo = (object) [
        'course' => $course->id,
        'module' => $module->id,
        'modulename' => 'customcert',
        'section' => 0,
        'name' => 'Certificat de réussite ISP',
        'intro' => '<p>Téléchargez votre certificat nominatif de réussite.</p>',
        'introformat' => FORMAT_HTML,
        'requiredtime' => 0,
        'verifyany' => 1,
        'deliveryoption' => 'D',
        'usecustomfilename' => 1,
        'customfilenamepattern' => 'Certificat-ISP-{FULLNAME}',
        'emailstudents' => 0,
        'emailteachers' => 0,
        'emailothers' => '',
        'protection_print' => 0,
        'protection_modify' => 1,
        'protection_copy' => 1,
        'language' => 'fr',
        'visible' => 1,
        'completion' => COMPLETION_TRACKING_AUTOMATIC,
        'completionview' => 1,
    ];
    $cm = add_moduleinfo($moduleinfo, $course, null);
    $certificate = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
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
    $messages[] = 'Certificat nominatif ajouté au cours de santé communautaire.';
}

// Only learners who pass the final quiz can open and generate the certificate.
$certificate = $DB->get_record('customcert', [
    'course' => $badgecourseid,
    'name' => 'Certificat de réussite ISP',
]);
if ($certificate) {
    $certificatecm = get_coursemodule_from_instance('customcert', $certificate->id, $badgecourseid, false, MUST_EXIST);
    $availability = json_encode([
        'op' => '&',
        'showc' => [true],
        'c' => [[
            'type' => 'completion',
            'cm' => $finalquizcmid,
            'e' => COMPLETION_COMPLETE_PASS,
        ]],
    ]);
    if ($certificatecm->availability !== $availability) {
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $certificatecm->id]);
        $messages[] = 'Accès au certificat limité à la réussite du quiz final.';
    }
}

// Provide administrators with a ready-to-use per-learner analytics report.
$analyticsreportname = 'Analytique ISP — progression et résultats';
$analyticssource = \core_course\reportbuilder\datasource\participants::class;
$analyticsreportrecord = $DB->get_record('reportbuilder_report', [
    'name' => $analyticsreportname,
    'source' => $analyticssource,
    'type' => \core_reportbuilder\datasource::TYPE_CUSTOM_REPORT,
]);
if ($analyticsreportrecord) {
    $analyticsreportid = (int) $analyticsreportrecord->id;
} else {
    $analyticsreport = report_helper::create_report((object) [
        'name' => $analyticsreportname,
        'source' => $analyticssource,
        'uniquerows' => 1,
        'tags' => ['ISP', 'analytique', 'progression'],
    ]);
    $analyticsreportid = (int) $analyticsreport->get('id');
    $messages[] = 'Rapport analytique ISP créé.';
}

$analyticscolumns = [
    'course_category:path',
    'role:name',
    'completion:progresspercent',
    'completion:completed',
    'completion:grade',
    'completion:timestarted',
    'completion:timecompleted',
    'access:timeaccess',
];
foreach ($analyticscolumns as $identifier) {
    if (!$DB->record_exists('reportbuilder_column', [
        'reportid' => $analyticsreportid,
        'uniqueidentifier' => $identifier,
    ])) {
        report_helper::add_report_column($analyticsreportid, $identifier);
    }
}

$analyticsfilters = [
    'course:fullname',
    'user:fullname',
    'role:name',
    'completion:completed',
    'completion:timecompleted',
    'access:timeaccess',
];
foreach ($analyticsfilters as $identifier) {
    if (!$DB->record_exists('reportbuilder_filter', [
        'reportid' => $analyticsreportid,
        'uniqueidentifier' => $identifier,
        'iscondition' => 0,
    ])) {
        report_helper::add_report_filter($analyticsreportid, $identifier);
    }
}

// Create a downloadable session-level attendance report for administrators.
if ($attendancemodule) {
    $attendancereportname = 'Présences — détail des séances';
    $attendancereportsource = \mod_attendance\reportbuilder\datasource\attendance::class;
    $attendancereportrecord = $DB->get_record('reportbuilder_report', [
        'name' => $attendancereportname,
        'source' => $attendancereportsource,
        'type' => \core_reportbuilder\datasource::TYPE_CUSTOM_REPORT,
    ]);
    if ($attendancereportrecord) {
        $attendancereportid = (int) $attendancereportrecord->id;
    } else {
        $attendancereport = report_helper::create_report((object) [
            'name' => $attendancereportname,
            'source' => $attendancereportsource,
            'uniquerows' => 1,
            'tags' => ['ISP', 'présences'],
        ]);
        $attendancereportid = (int) $attendancereport->get('id');
        $messages[] = 'Rapport détaillé des présences créé.';
    }
    foreach (['course:fullname', 'user:fullname', 'attendance:sessiondate', 'attendance:status'] as $identifier) {
        if (!$DB->record_exists('reportbuilder_filter', [
            'reportid' => $attendancereportid,
            'uniqueidentifier' => $identifier,
            'iscondition' => 0,
        ])) {
            report_helper::add_report_filter($attendancereportid, $identifier);
        }
    }
}

// Provision the end-to-end AI learner journey used for local acceptance tests.
require_once __DIR__ . '/configure-ai-course.php';

rebuild_course_cache(0, true);
// Configuration changes made through the CLI bypass admin-setting callbacks.
// Bump the theme revision so browsers fetch the new login image and CSS.
theme_reset_all_caches();
purge_all_caches();

echo "Configuration ISP terminée.\n";
foreach ($messages as $message) {
    echo '- ' . $message . "\n";
}
