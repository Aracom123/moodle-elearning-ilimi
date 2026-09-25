<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

/** Provision a narrowly scoped role and grant it to existing teachers. */
function xmldb_local_ispcoursecreation_install(): bool {
    \local_ispcoursecreation\local\access::sync_all();
    return true;
}
