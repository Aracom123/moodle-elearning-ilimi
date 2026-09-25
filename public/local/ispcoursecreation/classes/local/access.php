<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_ispcoursecreation\local;

defined('MOODLE_INTERNAL') || die();

use context_coursecat;
use context_system;

/** A single capability, assigned only in categories containing an assigned course. */
final class access {
    private const ROLENAME = 'isp_course_creator';
    private const COMPONENT = 'local_ispcoursecreation';

    public static function ensure_role(): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ROLENAME]);
        if (!$role) {
            $roleid = create_role(
                get_string('courserolename', self::COMPONENT),
                self::ROLENAME,
                get_string('courseroledescription', self::COMPONENT)
            );
            set_role_contextlevels($roleid, [CONTEXT_COURSECAT]);
        } else {
            $roleid = (int)$role->id;
            if (!$DB->record_exists('role_context_levels', [
                'roleid' => $roleid,
                'contextlevel' => CONTEXT_COURSECAT,
            ])) {
                set_role_contextlevels($roleid, [CONTEXT_COURSECAT]);
            }
        }

        $systemcontextid = context_system::instance()->id;
        if (!$DB->record_exists('role_capabilities', [
            'roleid' => $roleid,
            'contextid' => $systemcontextid,
            'capability' => 'moodle/course:create',
            'permission' => CAP_ALLOW,
        ])) {
            assign_capability('moodle/course:create', CAP_ALLOW, $roleid, $systemcontextid, true);
        }
        return $roleid;
    }

    public static function sync_user(int $userid): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ROLENAME], 'id');
        if (!$role) {
            return;
        }
        $roleid = (int)$role->id;
        $desired = [];
        if (!is_siteadmin($userid) && $DB->record_exists('user', [
            'id' => $userid, 'deleted' => 0, 'suspended' => 0,
        ])) {
            $categories = $DB->get_fieldset_sql(
                "SELECT DISTINCT c.category
                   FROM {role_assignments} ra
                   JOIN {role} r ON r.id = ra.roleid
                   JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :courselevel
                   JOIN {course} c ON c.id = ctx.instanceid
                  WHERE ra.userid = :userid
                    AND r.shortname = 'editingteacher'",
                ['courselevel' => CONTEXT_COURSE, 'userid' => $userid]
            );
            foreach ($categories as $categoryid) {
                $desired[(int)$categoryid] = true;
            }
        }

        $managed = $DB->get_records('role_assignments', [
            'roleid' => $roleid,
            'userid' => $userid,
            'component' => self::COMPONENT,
        ]);
        foreach ($managed as $assignment) {
            $context = \context::instance_by_id($assignment->contextid, IGNORE_MISSING);
            if (!$context || $context->contextlevel !== CONTEXT_COURSECAT
                    || !isset($desired[(int)$context->instanceid])) {
                role_unassign($roleid, $userid, $assignment->contextid, self::COMPONENT);
            } else {
                unset($desired[(int)$context->instanceid]);
            }
        }

        foreach (array_keys($desired) as $categoryid) {
            $context = context_coursecat::instance($categoryid, IGNORE_MISSING);
            if ($context && !$DB->record_exists('role_assignments', [
                'roleid' => $roleid, 'userid' => $userid, 'contextid' => $context->id,
            ])) {
                role_assign($roleid, $userid, $context->id, self::COMPONENT);
            }
        }
    }

    public static function sync_all(): void {
        global $DB;

        $teacherids = $DB->get_fieldset_sql(
            "SELECT DISTINCT ra.userid
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
              WHERE r.shortname = 'editingteacher'"
        );
        $roleid = self::ensure_role();
        $managedids = $DB->get_fieldset_select('role_assignments', 'userid',
            'roleid = :roleid AND component = :component',
            ['roleid' => $roleid, 'component' => self::COMPONENT]);
        foreach (array_unique(array_map('intval', array_merge($teacherids, $managedids))) as $userid) {
            self::sync_user($userid);
        }
    }
}
