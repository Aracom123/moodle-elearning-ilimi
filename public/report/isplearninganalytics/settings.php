<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('report/isplearninganalytics:view', context_system::instance())) {
    $ADMIN->add('reports', new admin_externalpage(
        'reportisplearninganalytics',
        get_string('pluginname', 'report_isplearninganalytics'),
        new moodle_url('/report/isplearninganalytics/index.php'),
        'report/isplearninganalytics:view'
    ));
}

