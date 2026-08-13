<?php
// This file is part of Moodle - http://moodle.org/.

namespace report_isplearninganalytics\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Builds learning analytics from existing Moodle data.
 */
final class analytics_service {
    /** A gap above this value starts a new active session. */
    public const SESSION_GAP_SECONDS = 1800;

    /** Minimum duration credited to a session containing at least one event. */
    public const MIN_SESSION_SECONDS = 60;

    /**
     * Return per-learner rows and course summaries.
     *
     * @param int $perioddays Number of days to include, or zero for all history.
     * @param int $courseid Optional course filter.
     * @return array{rows: array<int, stdClass>, summaries: array<int, stdClass>, since: int}
     */
    public function get_report(int $perioddays = 90, int $courseid = 0): array {
        global $DB;

        $since = $perioddays > 0 ? time() - ($perioddays * DAYSECS) : 0;
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'studentrole' => 'student',
            'siteid' => SITEID,
        ];
        $coursewhere = '';
        if ($courseid > 0) {
            $coursewhere = ' AND c.id = :courseid';
            $params['courseid'] = $courseid;
        }

        $sql = "SELECT CONCAT(c.id, '-', u.id) AS pairid,
                       c.id AS courseid, c.fullname AS coursename,
                       u.id AS userid, u.firstname, u.lastname
                  FROM {course} c
                  JOIN {context} ctx
                    ON ctx.contextlevel = :contextlevel AND ctx.instanceid = c.id
                  JOIN {role_assignments} ra ON ra.contextid = ctx.id
                  JOIN {role} r ON r.id = ra.roleid AND r.shortname = :studentrole
                  JOIN {user} u ON u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0
                 WHERE c.id <> :siteid {$coursewhere}
              ORDER BY c.sortorder, u.lastname, u.firstname";
        $pairs = $DB->get_records_sql($sql, $params);

        $rows = [];
        foreach ($pairs as $pair) {
            $row = new stdClass();
            $row->courseid = (int) $pair->courseid;
            $row->coursename = format_string($pair->coursename, true, ['context' => \context_course::instance($pair->courseid)]);
            $row->userid = (int) $pair->userid;
            $row->fullname = fullname($pair);
            $row->activeseconds = 0;
            $row->sessions = 0;
            $row->events = 0;
            $row->participations = 0;
            $row->activedays = [];
            $row->firstevent = 0;
            $row->lastevent = 0;
            $row->completionpercent = 0.0;
            $row->completed = false;
            $row->timecompleted = 0;
            $row->gradepercent = null;
            $row->_sessionstart = null;
            $row->_sessionlast = null;
            $rows[$pair->pairid] = $row;
        }

        if (!$rows) {
            return ['rows' => [], 'summaries' => $this->summarise_by_course([], $courseid), 'since' => $since];
        }

        $logparams = [
            'contextlevel' => CONTEXT_COURSE,
            'studentrole' => 'student',
            'since' => $since,
        ];
        $logcoursewhere = '';
        if ($courseid > 0) {
            $logcoursewhere = ' AND l.courseid = :logcourseid';
            $logparams['logcourseid'] = $courseid;
        }
        $logsql = "SELECT l.id, l.userid, l.courseid, l.timecreated, l.crud, l.component
                     FROM {logstore_standard_log} l
                     JOIN {context} ctx
                       ON ctx.contextlevel = :contextlevel AND ctx.instanceid = l.courseid
                     JOIN {role_assignments} ra
                       ON ra.contextid = ctx.id AND ra.userid = l.userid
                     JOIN {role} r ON r.id = ra.roleid AND r.shortname = :studentrole
                    WHERE l.timecreated >= :since AND l.userid > 0 AND l.courseid > 0 {$logcoursewhere}
                 ORDER BY l.courseid, l.userid, l.timecreated, l.id";
        $logs = $DB->get_recordset_sql($logsql, $logparams);
        foreach ($logs as $log) {
            $key = $log->courseid . '-' . $log->userid;
            if (!isset($rows[$key])) {
                continue;
            }
            $row = $rows[$key];
            $time = (int) $log->timecreated;
            $row->events++;
            if (str_starts_with((string) $log->component, 'mod_') && in_array($log->crud, ['c', 'u'], true)) {
                $row->participations++;
            }
            $row->activedays[date('Y-m-d', $time)] = true;
            $row->firstevent = $row->firstevent === 0 ? $time : min($row->firstevent, $time);
            $row->lastevent = max($row->lastevent, $time);

            if ($row->_sessionstart === null) {
                $row->_sessionstart = $time;
                $row->_sessionlast = $time;
                $row->sessions++;
            } else if (($time - $row->_sessionlast) > self::SESSION_GAP_SECONDS) {
                $row->activeseconds += max(self::MIN_SESSION_SECONDS, $row->_sessionlast - $row->_sessionstart);
                $row->_sessionstart = $time;
                $row->_sessionlast = $time;
                $row->sessions++;
            } else {
                $row->_sessionlast = $time;
            }
        }
        $logs->close();

        foreach ($rows as $row) {
            if ($row->_sessionstart !== null) {
                $row->activeseconds += max(self::MIN_SESSION_SECONDS, $row->_sessionlast - $row->_sessionstart);
            }
            $row->activedays = count($row->activedays);
            unset($row->_sessionstart, $row->_sessionlast);
        }

        $this->add_completion($rows, $courseid);
        $this->add_grades($rows, $courseid);

        return [
            'rows' => array_values($rows),
            'summaries' => $this->summarise_by_course($rows, $courseid),
            'since' => $since,
        ];
    }

    /** Add activity and course completion data to rows. */
    private function add_completion(array &$rows, int $courseid): void {
        global $DB;

        $params = ['siteid' => SITEID];
        $where = 'c.id <> :siteid';
        if ($courseid > 0) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = $courseid;
        }
        $tracked = $DB->get_records_sql_menu(
            "SELECT c.id, COUNT(cm.id)
               FROM {course} c
          LEFT JOIN {course_modules} cm
                 ON cm.course = c.id AND cm.completion > 0 AND cm.deletioninprogress = 0
              WHERE {$where}
           GROUP BY c.id",
            $params
        );

        $completionparams = [];
        $completionwhere = '';
        if ($courseid > 0) {
            $completionwhere = ' AND cc.course = :completioncourseid';
            $completionparams['completioncourseid'] = $courseid;
        }
        $coursecompletions = $DB->get_records_sql(
            "SELECT CONCAT(cc.course, '-', cc.userid) AS pairid, cc.timecompleted
               FROM {course_completions} cc
              WHERE 1 = 1 {$completionwhere}",
            $completionparams
        );

        $moduleparams = [];
        $modulewhere = '';
        if ($courseid > 0) {
            $modulewhere = ' AND cm.course = :modulecourseid';
            $moduleparams['modulecourseid'] = $courseid;
        }
        $modulecompletions = $DB->get_records_sql_menu(
            "SELECT CONCAT(cm.course, '-', cmc.userid) AS pairid, COUNT(DISTINCT cmc.coursemoduleid)
               FROM {course_modules_completion} cmc
               JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
              WHERE cm.completion > 0 AND cm.deletioninprogress = 0
                AND cmc.completionstate IN (1, 2) {$modulewhere}
           GROUP BY cm.course, cmc.userid",
            $moduleparams
        );

        foreach ($rows as $key => $row) {
            $total = (int) ($tracked[$row->courseid] ?? 0);
            $complete = (int) ($modulecompletions[$key] ?? 0);
            $row->completionpercent = $total > 0 ? min(100, round(($complete / $total) * 100, 1)) : 0.0;
            if (isset($coursecompletions[$key]) && !empty($coursecompletions[$key]->timecompleted)) {
                $row->completed = true;
                $row->timecompleted = (int) $coursecompletions[$key]->timecompleted;
                $row->completionpercent = 100.0;
            }
        }
    }

    /** Add normalized course grade percentages to rows. */
    private function add_grades(array &$rows, int $courseid): void {
        global $DB;

        $params = [];
        $where = '';
        if ($courseid > 0) {
            $where = ' AND gi.courseid = :gradecourseid';
            $params['gradecourseid'] = $courseid;
        }
        $grades = $DB->get_records_sql(
            "SELECT CONCAT(gi.courseid, '-', gg.userid) AS pairid,
                    gg.finalgrade, gi.grademin, gi.grademax
               FROM {grade_items} gi
               JOIN {grade_grades} gg ON gg.itemid = gi.id
              WHERE gi.itemtype = 'course' AND gg.finalgrade IS NOT NULL {$where}",
            $params
        );
        foreach ($grades as $key => $grade) {
            if (!isset($rows[$key])) {
                continue;
            }
            $range = (float) $grade->grademax - (float) $grade->grademin;
            if ($range > 0) {
                $rows[$key]->gradepercent = round(
                    (((float) $grade->finalgrade - (float) $grade->grademin) / $range) * 100,
                    1
                );
            }
        }
    }

    /** Build one aggregate row per course. */
    private function summarise_by_course(array $rows, int $courseid): array {
        global $DB;

        $summaries = [];
        $params = ['siteid' => SITEID];
        $where = 'id <> :siteid AND visible = 1';
        if ($courseid > 0) {
            $where .= ' AND id = :courseid';
            $params['courseid'] = $courseid;
        }
        $courses = $DB->get_records_select('course', $where, $params, 'sortorder', 'id,fullname');
        foreach ($courses as $course) {
            $summary = new stdClass();
            $summary->courseid = (int) $course->id;
            $summary->coursename = format_string(
                $course->fullname,
                true,
                ['context' => \context_course::instance($course->id)]
            );
            $summary->learners = 0;
            $summary->completed = 0;
            $summary->totalseconds = 0;
            $summary->totalevents = 0;
            $summary->totalparticipations = 0;
            $summary->completiontotal = 0.0;
            $summary->gradetotal = 0.0;
            $summary->gradecount = 0;
            $summaries[$course->id] = $summary;
        }
        foreach ($rows as $row) {
            if (!isset($summaries[$row->courseid])) {
                $summary = new stdClass();
                $summary->courseid = $row->courseid;
                $summary->coursename = $row->coursename;
                $summary->learners = 0;
                $summary->completed = 0;
                $summary->totalseconds = 0;
                $summary->totalevents = 0;
                $summary->totalparticipations = 0;
                $summary->completiontotal = 0.0;
                $summary->gradetotal = 0.0;
                $summary->gradecount = 0;
                $summaries[$row->courseid] = $summary;
            }
            $summary = $summaries[$row->courseid];
            $summary->learners++;
            $summary->completed += $row->completed ? 1 : 0;
            $summary->totalseconds += $row->activeseconds;
            $summary->totalevents += $row->events;
            $summary->totalparticipations += $row->participations;
            $summary->completiontotal += $row->completionpercent;
            if ($row->gradepercent !== null) {
                $summary->gradetotal += $row->gradepercent;
                $summary->gradecount++;
            }
        }

        foreach ($summaries as $summary) {
            $summary->averageseconds = $summary->learners > 0
                ? (int) round($summary->totalseconds / $summary->learners)
                : 0;
            $summary->averagecompletion = $summary->learners > 0
                ? round($summary->completiontotal / $summary->learners, 1)
                : 0.0;
            $summary->averagegrade = $summary->gradecount > 0
                ? round($summary->gradetotal / $summary->gradecount, 1)
                : null;
        }
        return array_values($summaries);
    }
}
