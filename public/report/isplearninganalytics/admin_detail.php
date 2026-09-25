<?php
// This file is part of Moodle - http://moodle.org/.

/** Detailed lists behind the administrator dashboard's activity and certificate totals. */

require_once dirname(__DIR__, 2) . '/config.php';

require_login();
require_capability('moodle/site:config', context_system::instance());

$view = optional_param('view', 'active', PARAM_ALPHA);
if (!in_array($view, ['active', 'certificates'], true)) {
    throw new moodle_exception('invalidparameter');
}
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 25;
$url = new moodle_url('/report/isplearninganalytics/admin_detail.php', ['view' => $view]);
$title = $view === 'active' ? 'Utilisateurs actifs · 30 jours' : 'Certificats délivrés';

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);

if ($view === 'active') {
    $params = ['since' => time() - 30 * DAYSECS];
    $total = $DB->count_records_select('user',
        'deleted = 0 AND suspended = 0 AND lastaccess >= :since', $params);
    $rows = $DB->get_records_sql(
        'SELECT id, firstname, lastname, email, lastaccess
           FROM {user}
          WHERE deleted = 0 AND suspended = 0 AND lastaccess >= :since
       ORDER BY lastaccess DESC, id DESC',
        $params, $page * $perpage, $perpage
    );
    $description = 'Comptes non suspendus ayant accédé à la plateforme au cours des 30 derniers jours.';
    $table = new html_table();
    $table->head = ['Utilisateur', 'Adresse courriel', 'Dernier accès'];
    foreach ($rows as $row) {
        $profile = new moodle_url('/user/profile.php', ['id' => $row->id]);
        $table->data[] = [
            html_writer::link($profile, s(fullname($row))),
            s($row->email),
            userdate($row->lastaccess, get_string('strftimedatetime', 'langconfig')),
        ];
    }
} else {
    $total = $DB->count_records('customcert_issues');
    $rows = $DB->get_records_sql(
        "SELECT ci.id, ci.userid, ci.timecreated, u.firstname, u.lastname,
                cc.name AS certificatename, c.fullname AS coursename, cm.id AS cmid
           FROM {customcert_issues} ci
      LEFT JOIN {user} u ON u.id = ci.userid
      LEFT JOIN {customcert} cc ON cc.id = ci.customcertid
      LEFT JOIN {course} c ON c.id = cc.course
      LEFT JOIN {modules} m ON m.name = 'customcert'
      LEFT JOIN {course_modules} cm ON cm.instance = cc.id AND cm.module = m.id
       ORDER BY ci.timecreated DESC, ci.id DESC",
        [], $page * $perpage, $perpage
    );
    $description = 'Tous les certificats émis sur la plateforme, du plus récent au plus ancien.';
    $table = new html_table();
    $table->head = ['Bénéficiaire', 'Certificat', 'Cours', 'Date de délivrance'];
    foreach ($rows as $row) {
        $name = $row->firstname !== null ? fullname($row) : 'Utilisateur nº ' . $row->userid;
        $profile = new moodle_url('/user/profile.php', ['id' => $row->userid]);
        $certificatename = $row->certificatename ?? 'Certificat supprimé';
        $certificate = $row->cmid
            ? html_writer::link(new moodle_url('/mod/customcert/view.php', ['id' => $row->cmid]),
                s($certificatename))
            : s($certificatename);
        $table->data[] = [
            html_writer::link($profile, s($name)),
            $certificate,
            s($row->coursename ?? 'Cours supprimé'),
            userdate($row->timecreated, get_string('strftimedatetime', 'langconfig')),
        ];
    }
}

$table->attributes['class'] = 'generaltable isp-analytics-table';

echo $OUTPUT->header();
echo html_writer::start_div('isp-admin-detail');
echo html_writer::link(new moodle_url('/my/'), '← Retour au tableau de bord',
    ['class' => 'isp-admin-detail-back']);
echo $OUTPUT->heading($title, 2);
echo html_writer::tag('p', $description, ['class' => 'isp-admin-detail-description']);
echo html_writer::tag('p', $total . ' résultat' . ($total > 1 ? 's' : ''),
    ['class' => 'isp-admin-detail-count']);
if ($total) {
    echo html_writer::div(html_writer::table($table), 'isp-admin-detail-table');
    echo $OUTPUT->paging_bar($total, $page, $perpage, $url);
} else {
    echo html_writer::tag('p', 'Aucune donnée à afficher pour le moment.', ['class' => 'alert alert-info']);
}
echo html_writer::end_div();
echo $OUTPUT->footer();
