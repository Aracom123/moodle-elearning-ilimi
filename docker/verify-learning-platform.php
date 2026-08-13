<?php
// Read-only verification of the ISP platform feature configuration.

define('CLI_SCRIPT', true);

require dirname(__DIR__) . '/public/config.php';
require_once $CFG->libdir . '/badgeslib.php';
require_once $CFG->libdir . '/completionlib.php';

global $DB, $CFG;

\core\session\manager::set_user(get_admin());

$checks = [];
$check = static function(string $label, bool $passed, string $detail = '') use (&$checks): void {
    $checks[] = [$label, $passed, $detail];
};

$check('Langue française', current_language() === 'fr', current_language());
$check('Thème Moove installé', (int) get_config('theme_moove', 'version') >= 2026042100, (string) get_config('theme_moove', 'version'));
$check('Thème Moove actif', (string) get_config('core', 'theme') === 'moove', (string) get_config('core', 'theme'));
$check('Configuration visuelle Moove',
    get_config('theme_moove', 'brandcolor') === '#6E3A41'
        && get_config('theme_moove', 'secondarymenucolor') === '#2A69B8'
        && get_config('theme_moove', 'fontsite') === 'Inter'
        && (int) get_config('theme_moove', 'displaymarketingbox') === 0,
    'bordeaux ISP, bleu secondaire, Inter, blocs marketing désactivés');
$systemcontext = context_system::instance();
$fs = get_file_storage();
$check(
    'Logo officiel ISP',
    $fs->file_exists($systemcontext->id, 'theme_moove', 'logo', 0, '/', 'isp-niger-logo.png')
        && $fs->file_exists($systemcontext->id, 'core_admin', 'logo', 0, '/', 'isp-niger-logo.png'),
    (string) get_config('theme_moove', 'logo')
);
$check(
    'Photo de connexion ISP',
    $fs->file_exists($systemcontext->id, 'theme_moove', 'loginbgimg', 0, '/', 'isp-niger-students.jpg'),
    (string) get_config('theme_moove', 'loginbgimg')
);
$check('Analytique Moodle', !empty($CFG->enableanalytics));
$check('Statistiques Moodle', !empty($CFG->enablestats));
$check('Complétion', !empty($CFG->enablecompletion));
$check('Badges', !empty($CFG->enablebadges));
$check('Rapports personnalisés', !empty($CFG->enablecustomreports));
$check('Compétences', (bool) get_config('core_competency', 'enabled'));
$check(
    'Custom certificate 5.2.5 installé',
    (int) get_config('mod_customcert', 'version') >= 2026042006,
    (string) get_config('mod_customcert', 'version')
);
$check(
    'Custom certificate configuré',
    (int) get_config('customcert', 'verifyallcertificates') === 1
        && (int) get_config('customcert', 'verifyany') === 1
        && (int) get_config('customcert', 'returncourse') === 1
        && (int) get_config('customcert', 'useadhoc') === 1,
    'vérification publique, retour au cours, tâches asynchrones'
);

$check(
    'Annonce d’accueil',
    $DB->record_exists('forum_discussions', ['name' => 'Bienvenue sur ISP eLearning'])
);
$check('Devoirs disponibles', $DB->count_records('assign') > 0, (string) $DB->count_records('assign'));
$check(
    'Extension Attendance Moodle 5.2',
    (int) get_config('mod_attendance', 'version') === 2026042101,
    (string) get_config('mod_attendance', 'version')
);
$check(
    'Registres de présence',
    $DB->count_records('attendance') === 7,
    (string) $DB->count_records('attendance')
);
$attendancestatuscount = $DB->count_records_select(
    'attendance_statuses',
    'attendanceid > 0 AND deleted = 0'
);
$check('Statuts de présence', $attendancestatuscount >= 28, (string) $attendancestatuscount);
$attendancegradeitems = $DB->count_records('grade_items', ['itemmodule' => 'attendance']);
$check('Notes de présence', $attendancegradeitems === 7, (string) $attendancegradeitems);
$attendancereport = $DB->get_record('reportbuilder_report', [
    'name' => 'Présences — détail des séances',
    'source' => \mod_attendance\reportbuilder\datasource\attendance::class,
]);
$check('Rapport détaillé des présences', (bool) $attendancereport);
if ($attendancereport) {
    try {
        $attendancetable = \core_reportbuilder\table\custom_report_table_view::create((int) $attendancereport->id);
        $attendancetable->setup();
        $attendancetable->query_db(5, false);
        $attendancetable->close_recordset();
        $check('Exécution du rapport de présences', true);
    } catch (Throwable $exception) {
        $check('Exécution du rapport de présences', false, $exception->getMessage());
    }
}
$check(
    'Suivi de progression sur les cours',
    $DB->count_records('block_instances', ['blockname' => 'completion_progress']) >= 7,
    (string) $DB->count_records('block_instances', ['blockname' => 'completion_progress'])
);
$check(
    'Référentiel santé publique',
    $DB->record_exists('competency_framework', ['idnumber' => 'ISP-SP-FONDAMENTAUX'])
);
$check(
    'Quatre compétences ISP',
    $DB->count_records_select('competency', $DB->sql_like('idnumber', ':prefix'), ['prefix' => 'ISP-SP-%']) === 4,
    (string) $DB->count_records_select('competency', $DB->sql_like('idnumber', ':prefix'), ['prefix' => 'ISP-SP-%'])
);
$check('Plans apprenants', $DB->count_records('competency_plan') > 0, (string) $DB->count_records('competency_plan'));

$badgerecord = $DB->get_record('badge', ['courseid' => 6]);
$check('Badge de cours actif', $badgerecord && (int) $badgerecord->status === BADGE_STATUS_ACTIVE);
if ($badgerecord) {
    $check(
        'Critère automatique du badge',
        $DB->record_exists('badge_criteria', [
            'badgeid' => $badgerecord->id,
            'criteriatype' => BADGE_CRITERIA_TYPE_COURSE,
        ])
    );
}

$certificate = $DB->get_record('customcert', ['course' => 6]);
$check('Certificat personnalisé', (bool) $certificate);
if ($certificate) {
    $cm = get_coursemodule_from_instance('customcert', $certificate->id, 6, false, MUST_EXIST);
    $availability = json_decode((string) $cm->availability, true);
    $check(
        'Certificat après réussite du quiz',
        ($availability['c'][0]['type'] ?? '') === 'completion'
            && (int) ($availability['c'][0]['cm'] ?? 0) === 74
            && (int) ($availability['c'][0]['e'] ?? 0) === COMPLETION_COMPLETE_PASS
    );
}

$check(
    'Règle de complétion du cours',
    $DB->record_exists('course_completion_criteria', [
        'course' => 6,
        'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
        'moduleinstance' => 74,
    ])
);

$aicourse = $DB->get_record('course', ['shortname' => 'IA-SANTE-PUBLIQUE-TEST']);
$check('Cours de démonstration IA', (bool) $aicourse);
if ($aicourse) {
    $aipages = $DB->count_records('page', ['course' => $aicourse->id]);
    $check('Ressources pédagogiques IA', $aipages >= 5, (string) $aipages);
    $aiquiz = $DB->get_record('quiz', [
        'course' => $aicourse->id,
        'name' => 'Quiz final — IA responsable en santé publique',
    ]);
    $check('Quiz final IA', (bool) $aiquiz);
    if ($aiquiz) {
        $aiquizcm = get_coursemodule_from_instance('quiz', $aiquiz->id, $aicourse->id, false, MUST_EXIST);
        $aiquestioncount = $DB->count_records('quiz_slots', ['quizid' => $aiquiz->id]);
        $check('Questions du quiz IA', $aiquestioncount >= 3, (string) $aiquestioncount);
        $check(
            'Seuil de réussite du quiz IA',
            (int) $aiquizcm->completionpassgrade === 70
                && (int) $aiquizcm->completiongradeitemnumber === 0,
            'seuil ' . $aiquizcm->completionpassgrade . ' %'
        );
        $aicriterion = $DB->record_exists('course_completion_criteria', [
            'course' => $aicourse->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
            'moduleinstance' => $aiquizcm->id,
        ]);
        $check('Complétion IA après réussite', $aicriterion);

        $aicertificate = $DB->get_record('customcert', [
            'course' => $aicourse->id,
            'name' => 'Certificat final — parcours IA',
        ]);
        $check('Certificat final IA', (bool) $aicertificate);
        if ($aicertificate) {
            $aicertcm = get_coursemodule_from_instance('customcert', $aicertificate->id, $aicourse->id, false, MUST_EXIST);
            $aiavailability = json_decode((string) $aicertcm->availability, true);
            $check(
                'Certificat IA après réussite du quiz',
                ($aiavailability['c'][0]['type'] ?? '') === 'completion'
                    && (int) ($aiavailability['c'][0]['cm'] ?? 0) === (int) $aiquizcm->id
                    && (int) ($aiavailability['c'][0]['e'] ?? 0) === COMPLETION_COMPLETE_PASS
            );
        }
    }
    $selfenrol = $DB->get_record('enrol', ['courseid' => $aicourse->id, 'enrol' => 'self']);
    $check('Auto-inscription IA sans clé', $selfenrol && (int) $selfenrol->status === ENROL_INSTANCE_ENABLED);
    $check('Compte étudiant IA de test', $DB->record_exists('user', ['username' => 'student.ia.test']));
}

$analyticsreport = $DB->get_record('reportbuilder_report', [
    'name' => 'Analytique ISP — progression et résultats',
    'source' => \core_course\reportbuilder\datasource\participants::class,
]);
$check('Rapport analytique ISP', (bool) $analyticsreport);
if ($analyticsreport) {
    $requiredcolumns = [
        'completion:progresspercent',
        'completion:completed',
        'completion:grade',
        'completion:timestarted',
        'completion:timecompleted',
        'access:timeaccess',
    ];
    $presentcolumns = $DB->get_fieldset_select(
        'reportbuilder_column',
        'uniqueidentifier',
        'reportid = :reportid',
        ['reportid' => $analyticsreport->id]
    );
    $check(
        'Indicateurs du rapport',
        count(array_diff($requiredcolumns, $presentcolumns)) === 0,
        implode(', ', $presentcolumns)
    );
}

$check(
    'Extension analytique ISP',
    (int) get_config('report_isplearninganalytics', 'version') === 2026081000,
    (string) get_config('report_isplearninganalytics', 'version')
);
$enabledstores = (string) get_config('tool_log', 'enabled_stores');
$check('Journal standard actif', str_contains($enabledstores, 'logstore_standard'), $enabledstores);
$standardlogcount = $DB->count_records('logstore_standard_log');
$check('Événements disponibles pour l’audit', $standardlogcount > 0, (string) $standardlogcount);
$configlogcount = $DB->count_records('config_log');
$check('Historique des configurations', $configlogcount > 0, (string) $configlogcount);
$analyticsservice = new \report_isplearninganalytics\local\analytics_service();
$analyticsdata = $analyticsservice->get_report(90, 0);
$realcoursecount = $DB->count_records_select('course', 'id <> :siteid', ['siteid' => SITEID]);
$check(
    'Calcul du temps actif',
    count($analyticsdata['rows']) > 0 && count($analyticsdata['summaries']) === $realcoursecount,
    count($analyticsdata['rows']) . ' ligne(s), ' . count($analyticsdata['summaries']) . ' cours'
);
$analyticsrow = reset($analyticsdata['rows']);
$check(
    'Indicateurs consolidés',
    $analyticsrow
        && property_exists($analyticsrow, 'activeseconds')
        && property_exists($analyticsrow, 'participations')
        && property_exists($analyticsrow, 'completionpercent')
        && property_exists($analyticsrow, 'gradepercent')
);

$failed = 0;
foreach ($checks as [$label, $passed, $detail]) {
    echo ($passed ? '[OK]   ' : '[FAIL] ') . $label;
    if ($detail !== '') {
        echo ' — ' . $detail;
    }
    echo PHP_EOL;
    if (!$passed) {
        $failed++;
    }
}

echo PHP_EOL . count($checks) . ' contrôles, ' . $failed . ' échec(s).' . PHP_EOL;
exit($failed === 0 ? 0 : 1);
