<?php
// This file is part of Moodle - http://moodle.org/.

namespace theme_moove\local;

defined('MOODLE_INTERNAL') || die();

/** Keeps the primary menu focused on the current user's profile. */
final class profile_navigation {
    public static function is_learner(int $userid): bool {
        global $DB;

        if (is_siteadmin($userid)) {
            return false;
        }
        return !$DB->record_exists_sql(
            "SELECT 1
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
              WHERE ra.userid = :userid
                AND r.shortname IN ('editingteacher', 'manager', 'coursecreator')",
            ['userid' => $userid]
        );
    }

    public static function filter_primary_menu(array $menu): array {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return $menu;
        }

        $hiddenkey = is_siteadmin($USER->id) ? 'mycourses'
            : (self::is_learner((int) $USER->id) ? 'myhome' : null);
        if ($hiddenkey === null) {
            return $menu;
        }
        $keep = static function(array $item) use ($hiddenkey): bool {
            return ($item['key'] ?? '') !== $hiddenkey;
        };
        if (isset($menu['moremenu']['nodearray'])) {
            $menu['moremenu']['nodearray'] = array_values(array_filter($menu['moremenu']['nodearray'], $keep));
        }
        if (isset($menu['mobileprimarynav'])) {
            $menu['mobileprimarynav'] = array_values(array_filter($menu['mobileprimarynav'], $keep));
        }
        return $menu;
    }
}
