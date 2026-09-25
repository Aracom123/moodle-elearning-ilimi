<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\role_assigned',
        'callback' => '\\local_ispcoursecreation\\observer::role_changed',
    ],
    [
        'eventname' => '\\core\\event\\role_unassigned',
        'callback' => '\\local_ispcoursecreation\\observer::role_changed',
    ],
    [
        'eventname' => '\\core\\event\\course_updated',
        'callback' => '\\local_ispcoursecreation\\observer::course_updated',
    ],
];
