<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Privacy provider.
 *
 * @package    block_statsdashboard
 * @copyright  2026 BAKO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_statsdashboard\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Le bloc n'enregistre aucune donnée personnelle.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Raison de l'absence de stockage de données.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
