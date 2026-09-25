<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_moove\local;

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_coursecat;
use moodle_url;

/** Read-only, role-aware overview for the personal dashboard. */
final class role_dashboard {
    /** @return array{role: string, html: string} */
    public static function render(int $userid, bool $onmycourses = false): array {
        if (is_siteadmin($userid)) {
            return ['role' => 'admin', 'html' => self::admin()];
        }

        $teaching = get_user_capability_course(
            'moodle/course:manageactivities', $userid, false, 'fullname, shortname, visible, category, enablecompletion'
        );
        unset($teaching[SITEID]);
        if ($teaching) {
            return ['role' => 'teacher', 'html' => self::teacher($teaching, $userid)];
        }
        return ['role' => 'learner', 'html' => self::learner($userid, $onmycourses)];
    }

    private static function card(string $label, string $value, ?moodle_url $url = null): string {
        $content = '<span class="isp-dashboard-stat-label">' . s($label) . '</span>'
            . '<strong class="isp-dashboard-stat-value">' . s($value) . '</strong>';
        if ($url) {
            return '<a class="isp-dashboard-stat" href="' . $url->out(false) . '">' . $content . '</a>';
        }
        return '<div class="isp-dashboard-stat">' . $content . '</div>';
    }

    private static function learner(int $userid, bool $onmycourses): string {
        global $DB;

        $courses = enrol_get_users_courses($userid, true, 'id');
        unset($courses[SITEID]);
        $certificatecount = $DB->count_records('customcert_issues', ['userid' => $userid]);
        $certificates = $DB->get_records_sql(
            "SELECT ci.id, c.fullname AS coursename, cm.id AS cmid, ci.timecreated
               FROM {customcert_issues} ci
               JOIN {customcert} cc ON cc.id = ci.customcertid
               JOIN {course} c ON c.id = cc.course
               JOIN {modules} m ON m.name = 'customcert'
               JOIN {course_modules} cm ON cm.instance = cc.id AND cm.module = m.id
              WHERE ci.userid = :userid
           ORDER BY ci.timecreated DESC",
            ['userid' => $userid], 0, 8
        );

        $coursesurl = new moodle_url('/my/courses.php');
        $coursesurl->set_anchor('isp-my-courses-list');
        $out = '<div class="isp-role-dashboard isp-role-dashboard--learner">';
        $out .= '<div class="isp-dashboard-lead"><div><h2>Votre parcours</h2>'
            . '<p>Suivez vos acquis et votre activité.</p></div>';
        if (!$onmycourses) {
            $out .= '<a class="isp-dashboard-courses-link" href="'
                . $coursesurl->out(false) . '">Voir mes cours</a>';
        }
        $out .= '</div>';
        $out .= '<div class="isp-dashboard-stats">'
            . self::card('Cours suivis', (string) count($courses), $coursesurl)
            . self::card('Certificats obtenus', (string) $certificatecount)
            . '</div>';
        $out .= '<div class="isp-dashboard-panels">';
        $out .= '<section class="isp-dashboard-panel" aria-labelledby="isp-certificates-title"><h3 id="isp-certificates-title">Mes certifications</h3>';
        if ($certificates) {
            $out .= '<ul class="isp-dashboard-link-list">';
            foreach ($certificates as $certificate) {
                $url = new moodle_url('/mod/customcert/view.php', ['id' => $certificate->cmid]);
                $out .= '<li><a href="' . $url->out(false) . '">' . s($certificate->coursename) . '</a>'
                    . '<span>' . userdate($certificate->timecreated, get_string('strftimedate', 'langconfig')) . '</span></li>';
            }
            $out .= '</ul>';
        } else {
            $out .= '<p class="isp-dashboard-empty">Aucun certificat délivré pour le moment. Vos certificats apparaîtront ici après validation des parcours concernés.</p>';
        }
        $out .= '</section>';
        $out .= self::engagement($userid);
        $out .= '</div></div>';
        return $out;
    }

    private static function engagement(int $userid): string {
        global $DB;

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTimeImmutable('first day of this month');
            $date = $date->modify("-{$i} months");
            $months[$date->format('Y-m')] = 0;
        }
        $since = (new \DateTimeImmutable('first day of this month'))->modify('-5 months')->getTimestamp();
        $logs = $DB->get_recordset_sql(
            'SELECT id, timecreated FROM {logstore_standard_log}
              WHERE userid = :userid AND timecreated >= :since
           ORDER BY timecreated, id',
            ['userid' => $userid, 'since' => $since]
        );
        $start = null;
        $last = null;
        $credit = static function(?int $start, ?int $last) use (&$months): void {
            if ($start === null || $last === null) {
                return;
            }
            $key = date('Y-m', $start);
            if (isset($months[$key])) {
                $months[$key] += max(60, $last - $start);
            }
        };
        foreach ($logs as $log) {
            $time = (int) $log->timecreated;
            if ($last !== null && $time - $last > 1800) {
                $credit($start, $last);
                $start = $time;
            }
            $start ??= $time;
            $last = $time;
        }
        $logs->close();
        $credit($start, $last);

        $maximum = max(1, ...array_values($months));
        $out = '<section class="isp-dashboard-panel" aria-labelledby="isp-engagement-title">'
            . '<h3 id="isp-engagement-title">Mon temps d’engagement</h3>'
            . '<p class="isp-dashboard-note">Estimation par sessions d’activité ; les périodes sans interaction ne sont pas comptées.</p>'
            . '<div class="isp-engagement-chart">';
        foreach ($months as $month => $seconds) {
            $duration = $seconds < HOURSECS
                ? max(0, (int) round($seconds / MINSECS)) . ' min'
                : format_float($seconds / HOURSECS, 1) . ' h';
            $label = userdate(strtotime($month . '-01'), '%b');
            $out .= '<div class="isp-engagement-month"><span class="isp-engagement-amount">' . s($duration) . '</span>'
                . '<div class="isp-engagement-track"><span style="height:' . round(100 * $seconds / $maximum) . '%"></span></div>'
                . '<span class="isp-engagement-label">' . s($label) . '</span></div>';
        }
        return $out . '</div></section>';
    }

    private static function teacher(array $courses, int $userid): string {
        global $DB;

        $studentcount = 0;
        $coursemetrics = [];
        $creatablecategories = [];
        foreach ($courses as $course) {
            $categoryid = (int) $course->category;
            if (!isset($creatablecategories[$categoryid])
                    && has_capability('moodle/course:create', context_coursecat::instance($categoryid), $userid)) {
                $creatablecategories[$categoryid] = $DB->get_field('course_categories', 'name', ['id' => $categoryid]);
            }
            $context = context_course::instance($course->id);
            [$enrolledsql, $params] = get_enrolled_sql($context, '', 0, true);
            $params['contextid'] = $context->id;
            $enrolled = (int) $DB->count_records_sql(
                "SELECT COUNT(DISTINCT ra.userid)
                   FROM {role_assignments} ra
                   JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                   JOIN {user} u ON u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0
                   JOIN ({$enrolledsql}) eu ON eu.id = ra.userid
                  WHERE ra.contextid = :contextid",
                $params
            );
            $studentcount += $enrolled;
            $tracked = (int) $DB->count_records_select('course_modules',
                'course = :courseid AND completion > 0 AND deletioninprogress = 0', ['courseid' => $course->id]);
            $progress = null;
            if ($enrolled > 0 && $tracked > 0) {
                [$enrolledsql, $progressparams] = get_enrolled_sql($context, '', 0, true);
                $progressparams['contextid'] = $context->id;
                $progressparams['courseid'] = $course->id;
                $completed = (int) $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT cmc.id) FROM {course_modules_completion} cmc
                       JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                       JOIN {role_assignments} ra ON ra.userid = cmc.userid AND ra.contextid = :contextid
                       JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
                       JOIN ({$enrolledsql}) eu ON eu.id = cmc.userid
                      WHERE cm.course = :courseid AND cm.completion > 0 AND cm.deletioninprogress = 0
                        AND cmc.completionstate IN (1, 2)",
                    $progressparams
                );
                $progress = min(100, (int) round(100 * $completed / ($enrolled * $tracked)));
            }
            $coursemetrics[] = ['course' => $course, 'students' => $enrolled, 'progress' => $progress];
        }
        $weeklyactivity = self::teacher_weekly_activity(array_keys($courses));
        $out = '<div class="isp-role-dashboard isp-role-dashboard--teacher">'
            . '<div class="isp-dashboard-lead"><div><h2>Enseignement</h2>'
            . '<p>Gérez vos cours et suivez les étudiants qui y sont inscrits.</p></div>';
        $out .= '<div class="isp-dashboard-lead-actions">'
            . '<a class="isp-dashboard-courses-link" href="'
            . (new moodle_url('/my/courses.php'))->out(false) . '">Voir mes cours</a>';
        if ($creatablecategories) {
            $multiplecategories = count($creatablecategories) > 1;
            $out .= '<div class="isp-dashboard-create-actions">';
            foreach ($creatablecategories as $categoryid => $categoryname) {
                $createurl = new moodle_url('/course/edit.php', ['category' => $categoryid]);
                $label = $multiplecategories ? 'Ajouter un cours — ' . $categoryname : 'Ajouter un cours';
                $out .= '<a class="isp-dashboard-create-course" href="' . $createurl->out(false) . '">'
                    . s($label) . '</a>';
            }
            $out .= '</div>';
        }
        $out .= '</div></div>'
            . '<div class="isp-dashboard-stats">'
            . self::card('Cours gérés', (string) count($courses))
            . self::card('Inscriptions étudiantes', (string) $studentcount)
            . '</div>';
        $out .= self::teacher_charts($coursemetrics, $weeklyactivity);
        $out .= '<section class="isp-dashboard-panel"><h3>Statistiques des cours</h3>';
        if (count($courses) === 1) {
            $course = reset($courses);
            $reports = new moodle_url('/report/isplearninganalytics/index.php', ['courseid' => $course->id]);
            $out .= '<a href="' . $reports->out(false) . '">Consulter les statistiques</a>';
        } else {
            $out .= '<details class="isp-dashboard-report-chooser"><summary>Choisir un cours</summary>'
                . '<ul class="isp-dashboard-link-list">';
            foreach ($courses as $course) {
                $reports = new moodle_url('/report/isplearninganalytics/index.php', ['courseid' => $course->id]);
                $out .= '<li><a href="' . $reports->out(false) . '">' . s($course->fullname) . '</a></li>';
            }
            $out .= '</ul></details>';
        }
        return $out . '</section></div>';
    }

    /** Distinct enrolled students active in each of the past four seven-day periods. */
    private static function teacher_weekly_activity(array $courseids): array {
        global $DB;

        $weeks = [0, 0, 0, 0];
        if (!$courseids) {
            return $weeks;
        }
        $now = time();
        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'teachercourse');
        $params = $inparams + [
            'since' => $now - 28 * DAYSECS,
            'nowforweek' => $now,
            'nowforlog' => $now,
            'nowforstart' => $now,
            'nowforend' => $now,
            'contextlevel' => CONTEXT_COURSE,
            'studentrole' => 'student',
            'enrolactive' => ENROL_USER_ACTIVE,
            'enrolenabled' => ENROL_INSTANCE_ENABLED,
        ];
        $rows = $DB->get_records_sql(
            "SELECT FLOOR((:nowforweek - l.timecreated) / " . (7 * DAYSECS) . ") AS weekindex,
                    COUNT(DISTINCT l.userid) AS students
               FROM {logstore_standard_log} l
               JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = l.courseid
               JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = l.userid
               JOIN {role} r ON r.id = ra.roleid AND r.shortname = :studentrole
               JOIN {user} u ON u.id = l.userid AND u.deleted = 0 AND u.suspended = 0
              WHERE l.courseid {$insql} AND l.timecreated >= :since AND l.timecreated <= :nowforlog
                AND EXISTS (SELECT 1 FROM {user_enrolments} ue
                              JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = l.courseid
                             WHERE ue.userid = l.userid AND ue.status = :enrolactive
                               AND e.status = :enrolenabled AND ue.timestart <= :nowforstart
                               AND (ue.timeend = 0 OR ue.timeend > :nowforend))
           GROUP BY 1",
            $params
        );
        foreach ($rows as $row) {
            $index = (int) $row->weekindex;
            if ($index >= 0 && $index < 4) {
                $weeks[3 - $index] = (int) $row->students;
            }
        }
        return $weeks;
    }

    /** Accessible, server-rendered charts for the teacher's own courses. */
    private static function teacher_charts(array $metrics, array $weeklyactivity): string {
        $out = '<div class="isp-teacher-charts">';
        $maxstudents = max(1, ...array_column($metrics, 'students'));
        $out .= '<section class="isp-dashboard-panel"><h3>Inscriptions par cours</h3>'
            . '<p class="isp-dashboard-note">Étudiants inscrits actuellement dans chaque cours.</p>'
            . '<div class="isp-teacher-chart-rows">';
        foreach ($metrics as $metric) {
            $out .= self::teacher_chart_row($metric['course']->fullname,
                $metric['students'] . ' étudiant' . ($metric['students'] === 1 ? '' : 's'),
                100 * $metric['students'] / $maxstudents);
        }
        $out .= '</div></section><section class="isp-dashboard-panel"><h3>Progression moyenne</h3>'
            . '<p class="isp-dashboard-note">Part des activités suivies achevées par les étudiants inscrits.</p>'
            . '<div class="isp-teacher-chart-rows">';
        foreach ($metrics as $metric) {
            $label = $metric['progress'] === null ? 'Suivi non disponible' : $metric['progress'] . ' %';
            $out .= self::teacher_chart_row($metric['course']->fullname, $label, $metric['progress'] ?? 0);
        }
        $out .= '</div></section><section class="isp-dashboard-panel isp-teacher-chart-wide">'
            . '<h3>Étudiants actifs · 4 semaines</h3>'
            . '<p class="isp-dashboard-note">Étudiants distincts ayant eu une activité dans vos cours, par période de 7 jours.</p>'
            . '<div class="isp-teacher-week-chart">';
        $maxactive = max(1, ...$weeklyactivity);
        foreach ($weeklyactivity as $index => $count) {
            $weeklabel = $index === 3 ? '7 derniers jours' : 'Semaine ' . ($index + 1);
            $out .= '<div class="isp-teacher-week"><strong>' . $count . '</strong>'
                . '<div class="isp-teacher-week-track"><span style="height:'
                . round(100 * $count / $maxactive) . '%"></span></div>'
                . '<span>' . s($weeklabel) . '</span></div>';
        }
        return $out . '</div></section></div>';
    }

    private static function teacher_chart_row(string $name, string $value, float $percent): string {
        return '<div class="isp-teacher-chart-row"><div class="isp-teacher-chart-meta">'
            . '<span>' . s($name) . '</span><strong>' . s($value) . '</strong></div>'
            . '<div class="isp-teacher-chart-track"><span style="width:' . round($percent) . '%"></span></div></div>';
    }

    private static function admin(): string {
        global $DB;

        $courses = $DB->count_records_select('course', 'id <> :siteid', ['siteid' => SITEID]);
        $users = $DB->count_records_select('user', 'deleted = 0 AND suspended = 0 AND id > 1');
        $active = $DB->count_records_select('user', 'deleted = 0 AND suspended = 0 AND lastaccess >= :since',
            ['since' => time() - 30 * DAYSECS]);
        $certificates = $DB->count_records('customcert_issues');
        $out = '<div class="isp-role-dashboard isp-role-dashboard--admin">'
            . '<div class="isp-dashboard-lead"><h2>Pilotage de la plateforme</h2><p>Vue consolidée des comptes, des cours et des résultats.</p></div>'
            . '<div class="isp-dashboard-stats">'
            . self::card('Cours', (string) $courses, new moodle_url('/course/management.php'))
            . self::card('Comptes actifs', (string) $users, new moodle_url('/admin/user.php'))
            . self::card('Utilisateurs actifs · 30 jours', (string) $active,
                new moodle_url('/report/isplearninganalytics/admin_detail.php', ['view' => 'active']))
            . self::card('Certificats délivrés', (string) $certificates,
                new moodle_url('/report/isplearninganalytics/admin_detail.php', ['view' => 'certificates']))
            . '</div><section class="isp-dashboard-panel"><h3>Rapports et administration</h3>'
            . '<div class="isp-dashboard-actions">'
            . '<a href="' . (new moodle_url('/report/isplearninganalytics/index.php'))->out(false) . '">Progression et résultats</a>'
            . '<a href="' . (new moodle_url('/reportbuilder/index.php'))->out(false) . '">Rapports personnalisés</a>'
            . '<a href="' . (new moodle_url('/admin/search.php'))->out(false) . '">Administration du site</a>'
            . '</div></section></div>';
        return $out;
    }
}
