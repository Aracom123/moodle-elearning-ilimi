<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Calcul des statistiques de la plateforme.
 *
 * @package    block_statsdashboard
 * @copyright  2026 BAKO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_statsdashboard\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Fournit les indicateurs agrégés affichés par le bloc.
 */
class stats {

    /** @var int Fenêtre d'analyse en jours pour l'activité et la durée moyenne. */
    const PERIOD_DAYS = 30;

    /** @var int Écart max (secondes) entre 2 événements d'une même session. */
    const SESSION_GAP = 1800;

    /**
     * Nombre de cours visibles (hors page d'accueil).
     *
     * @return int
     */
    public static function count_courses(): int {
        global $DB;
        return (int) $DB->count_records_select('course', 'id <> ?', [SITEID]);
    }

    /**
     * Nombre d'utilisateurs distincts ayant un rôle d'un archétype donné.
     *
     * @param array $archetypes Archétypes de rôle (ex. ['student']).
     * @return int
     */
    public static function count_users_by_archetype(array $archetypes): int {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($archetypes, SQL_PARAMS_NAMED);
        $sql = "SELECT COUNT(DISTINCT ra.userid)
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {user} u ON u.id = ra.userid
                 WHERE r.archetype $insql
                   AND u.deleted = 0 AND u.suspended = 0";
        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Taux de complétion global : complétions achevées / suivis de complétion.
     *
     * @return float|null Pourcentage ou null si le suivi n'est pas utilisé.
     */
    public static function completion_rate(): ?float {
        global $DB;
        $total = $DB->count_records('course_completions');
        if ($total === 0) {
            return null;
        }
        $completed = $DB->count_records_select('course_completions', 'timecompleted IS NOT NULL');
        return round($completed * 100 / $total, 1);
    }

    /**
     * Certifications délivrées, tous plugins de certificat confondus.
     *
     * @return array ['certificates' => int|null, 'badges' => int]
     */
    public static function certificates_issued(): array {
        global $DB;
        $dbman = $DB->get_manager();
        $count = null;
        // Tables des plugins de certificat les plus courants.
        foreach (['customcert_issues', 'tool_certificate_issues', 'certificate_issues'] as $table) {
            if ($dbman->table_exists($table)) {
                $count = (int) ($count ?? 0) + $DB->count_records($table);
            }
        }
        $badges = $dbman->table_exists('badge_issued') ? $DB->count_records('badge_issued') : 0;
        return ['certificates' => $count, 'badges' => $badges];
    }

    /**
     * Durée moyenne passée sur la plateforme par apprenant sur la période,
     * estimée par sessions (écart entre événements < SESSION_GAP).
     *
     * @return int|null Durée moyenne en secondes, ou null si pas de logs.
     */
    public static function average_duration_per_learner(): ?int {
        global $DB;

        $since = time() - self::PERIOD_DAYS * DAYSECS;
        $sql = "SELECT l.id, l.userid, l.timecreated
                  FROM {logstore_standard_log} l
                 WHERE l.timecreated >= :since AND l.userid > 0
              ORDER BY l.userid ASC, l.timecreated ASC";
        $rs = $DB->get_recordset_sql($sql, ['since' => $since]);

        $totaltime = 0;
        $users = [];
        $lastuser = null;
        $lasttime = null;
        foreach ($rs as $log) {
            $users[$log->userid] = true;
            if ($lastuser === $log->userid) {
                $gap = $log->timecreated - $lasttime;
                if ($gap > 0 && $gap < self::SESSION_GAP) {
                    $totaltime += $gap;
                }
            }
            $lastuser = $log->userid;
            $lasttime = $log->timecreated;
        }
        $rs->close();

        $nbusers = count($users);
        return $nbusers > 0 ? (int) ($totaltime / $nbusers) : null;
    }

    /**
     * Activité par jour sur la période : événements et utilisateurs actifs.
     *
     * @return array ['labels' => string[], 'events' => int[], 'users' => int[]]
     */
    public static function activity_by_day(): array {
        global $DB;

        $days = self::PERIOD_DAYS;
        $start = usergetmidnight(time() - ($days - 1) * DAYSECS);

        $sql = "SELECT l.timecreated, l.userid
                  FROM {logstore_standard_log} l
                 WHERE l.timecreated >= :start AND l.userid > 0";
        $rs = $DB->get_recordset_sql($sql, ['start' => $start]);

        $events = array_fill(0, $days, 0);
        $usersperday = array_fill(0, $days, []);
        foreach ($rs as $log) {
            $index = (int) floor(($log->timecreated - $start) / DAYSECS);
            if ($index >= 0 && $index < $days) {
                $events[$index]++;
                $usersperday[$index][$log->userid] = true;
            }
        }
        $rs->close();

        $labels = [];
        $users = [];
        for ($i = 0; $i < $days; $i++) {
            $labels[] = userdate($start + $i * DAYSECS, '%d/%m');
            $users[] = count($usersperday[$i]);
        }

        return ['labels' => $labels, 'events' => $events, 'users' => $users];
    }

    /**
     * Formate une durée en h/min.
     *
     * @param int $seconds Durée en secondes.
     * @return string
     */
    public static function format_duration(int $seconds): string {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return $hours > 0 ? sprintf('%dh %02dmin', $hours, $minutes) : sprintf('%dmin', $minutes);
    }
}
