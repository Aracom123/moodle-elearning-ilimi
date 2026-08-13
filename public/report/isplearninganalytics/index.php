<?php
// This file is part of Moodle - http://moodle.org/.

require_once dirname(__DIR__, 2) . '/config.php';

use report_isplearninganalytics\local\analytics_service;

require_login();
$context = context_system::instance();
require_capability('report/isplearninganalytics:view', $context);

$courseid = optional_param('courseid', 0, PARAM_INT);
$period = optional_param('period', 90, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
if (!in_array($period, [0, 30, 90, 365], true)) {
    $period = 90;
}

$url = new moodle_url('/report/isplearninganalytics/index.php', ['courseid' => $courseid, 'period' => $period]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_isplearninganalytics'));
$PAGE->set_heading(get_string('pluginname', 'report_isplearninganalytics'));

$service = new analytics_service();
$data = $service->get_report($period, $courseid);

$formatduration = static function(int $seconds): string {
    if ($seconds <= 0) {
        return '0 min';
    }
    $hours = intdiv($seconds, HOURSECS);
    $minutes = (int) round(($seconds % HOURSECS) / MINSECS);
    return $hours > 0 ? $hours . ' h ' . $minutes . ' min' : max(1, $minutes) . ' min';
};

if ($download === 'csv') {
    require_sesskey();
    $filename = clean_filename('analytique-isp-' . userdate(time(), '%Y-%m-%d') . '.csv');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'wb');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, [
        'Cours', 'Apprenant', 'Temps actif estimé', 'Sessions', 'Jours actifs',
        'Événements', 'Participations', 'Progression (%)', 'Cours terminé', 'Note finale (%)', 'Dernier événement',
    ]);
    foreach ($data['rows'] as $row) {
        fputcsv($output, [
            $row->coursename,
            $row->fullname,
            $formatduration($row->activeseconds),
            $row->sessions,
            $row->activedays,
            $row->events,
            $row->participations,
            $row->completionpercent,
            $row->completed ? 'Oui' : 'Non',
            $row->gradepercent ?? '',
            $row->lastevent ? userdate($row->lastevent) : '',
        ]);
    }
    fclose($output);
    exit;
}

$courseoptions = [0 => 'Tous les cours'];
foreach ($DB->get_records_select('course', 'id <> :siteid AND visible = 1', ['siteid' => SITEID], 'sortorder', 'id,fullname') as $course) {
    $courseoptions[$course->id] = format_string($course->fullname);
}
$periodoptions = [30 => '30 derniers jours', 90 => '90 derniers jours', 365 => '12 derniers mois', 0 => 'Tout l’historique'];

echo $OUTPUT->header();
echo html_writer::start_div('isp-analytics-intro');
echo html_writer::tag('p',
    'Vue consolidée de la progression, de la complétion, de la participation, des notes et du temps actif estimé.'
);
echo html_writer::tag('p',
    'Méthode du temps actif : les événements Moodle sont regroupés en sessions ; une interruption de plus de 30 minutes '
    . 'ouvre une nouvelle session. Une session observée compte au minimum une minute.',
    ['class' => 'small text-muted']
);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'action' => (new moodle_url('/report/isplearninganalytics/index.php'))->out(false), 'class' => 'isp-analytics-filters']);
echo html_writer::label('Cours', 'id_courseid');
echo html_writer::select($courseoptions, 'courseid', $courseid, false, ['id' => 'id_courseid', 'class' => 'custom-select']);
echo html_writer::label('Période', 'id_period');
echo html_writer::select($periodoptions, 'period', $period, false, ['id' => 'id_period', 'class' => 'custom-select']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Appliquer', 'class' => 'btn btn-primary']);
echo html_writer::link(
    new moodle_url($url, ['download' => 'csv', 'sesskey' => sesskey()]),
    'Exporter les données CSV',
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_tag('form');

echo $OUTPUT->heading('Vue par cours', 2);
$summarytable = new html_table();
$summarytable->attributes['class'] = 'generaltable isp-analytics-table';
$summarytable->head = [
    'Cours', 'Apprenants', 'Terminés', 'Progression moyenne', 'Temps actif moyen', 'Note moyenne', 'Participations', 'Événements',
];
foreach ($data['summaries'] as $summary) {
    $summarytable->data[] = [
        html_writer::link(new moodle_url('/course/view.php', ['id' => $summary->courseid]), s($summary->coursename)),
        $summary->learners,
        $summary->completed,
        format_float($summary->averagecompletion, 1) . ' %',
        $formatduration($summary->averageseconds),
        $summary->averagegrade === null ? '—' : format_float($summary->averagegrade, 1) . ' %',
        $summary->totalparticipations,
        $summary->totalevents,
    ];
}
echo html_writer::table($summarytable);

echo $OUTPUT->heading('Détail par apprenant', 2);
$detailtable = new html_table();
$detailtable->attributes['class'] = 'generaltable isp-analytics-table';
$detailtable->head = [
    'Cours', 'Apprenant', 'Temps actif', 'Sessions', 'Jours actifs', 'Participations', 'Progression', 'Terminé', 'Note', 'Dernière activité',
];
foreach ($data['rows'] as $row) {
    $detailtable->data[] = [
        s($row->coursename),
        html_writer::link(new moodle_url('/user/profile.php', ['id' => $row->userid]), s($row->fullname)),
        $formatduration($row->activeseconds),
        $row->sessions,
        $row->activedays,
        $row->participations,
        format_float($row->completionpercent, 1) . ' %',
        $row->completed ? 'Oui' : 'Non',
        $row->gradepercent === null ? '—' : format_float($row->gradepercent, 1) . ' %',
        $row->lastevent ? userdate($row->lastevent) : 'Jamais',
    ];
}
echo html_writer::table($detailtable);

echo html_writer::start_div('isp-analytics-audit');
echo $OUTPUT->heading('Audit et traçabilité', 2);
echo html_writer::tag('p', 'Les indicateurs ci-dessus sont calculés à partir du journal standard, des inscriptions, de la complétion et du carnet de notes Moodle.');
echo html_writer::link(new moodle_url('/report/configlog/index.php'), 'Modifications de configuration', ['class' => 'btn btn-outline-primary mr-2']);
echo html_writer::link(new moodle_url('/admin/tasklogs.php'), 'Journaux des tâches planifiées', ['class' => 'btn btn-outline-primary']);
echo html_writer::end_div();

echo $OUTPUT->footer();
