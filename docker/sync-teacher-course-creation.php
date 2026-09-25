<?php
// Reconcile ISP course-creation grants after a restore or manual role changes.

define('CLI_SCRIPT', true);
require dirname(__DIR__) . '/public/config.php';

\core\session\manager::set_user(get_admin());
\local_ispcoursecreation\local\access::sync_all();
echo "Teacher course-creation permissions synchronized.\n";
