<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_ispcoursecreation;

defined('MOODLE_INTERNAL') || die();

use context_course;
use local_ispcoursecreation\local\access;

/** Keep category-level creation access aligned with teaching assignments. */
final class observer {
    public static function role_changed(\core\event\base $event): void {
        global $DB;

        if ($event->get_context()->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $role = $DB->get_record('role', ['id' => $event->objectid], 'shortname');
        if (!$role || $role->shortname !== 'editingteacher') {
            return;
        }
        access::sync_user((int)$event->relateduserid);
    }

    public static function course_updated(\core\event\course_updated $event): void {
        global $DB;

        if (empty($event->other['updatedfields']['category'])) {
            return;
        }
        $context = context_course::instance((int)$event->objectid, IGNORE_MISSING);
        if (!$context) {
            return;
        }
        $teacherids = $DB->get_fieldset_sql(
            "SELECT DISTINCT ra.userid
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
              WHERE ra.contextid = :contextid
                AND r.shortname = 'editingteacher'",
            ['contextid' => $context->id]
        );
        foreach ($teacherids as $userid) {
            access::sync_user((int)$userid);
        }
    }
}
