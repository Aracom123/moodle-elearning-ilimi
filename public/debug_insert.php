<?php
// Nettoyage des activités de test créées pendant le diagnostic
require_once('../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_login();
if (!is_siteadmin()) die('Accès refusé.');
header('Content-Type: text/html; charset=utf-8');
echo "<pre style='font-size:13px'>";

$testnames = ['TEST DEBUG PAGE','TEST FORUM DBG','TEST ASSIGN DBG','TEST URL DBG'];
$modmap = ['page'=>'page','forum'=>'forum','assign'=>'assign','url'=>'url'];

foreach ($modmap as $table => $modname) {
    $mod = $DB->get_record('modules', ['name'=>$modname]);
    foreach ($testnames as $tn) {
        $instances = $DB->get_records($table, ['name'=>$tn]);
        foreach ($instances as $inst) {
            $cm = $DB->get_record('course_modules', ['module'=>$mod->id, 'instance'=>$inst->id]);
            if ($cm) {
                try { course_delete_module($cm->id); echo "Supprimé : $tn (cm {$cm->id})\n"; }
                catch (Throwable $e) {
                    // suppression manuelle de secours
                    $DB->delete_records('course_modules', ['id'=>$cm->id]);
                    $DB->delete_records($table, ['id'=>$inst->id]);
                    echo "Supprimé (fallback) : $tn\n";
                }
            } else {
                $DB->delete_records($table, ['id'=>$inst->id]);
                echo "Instance orpheline supprimée : $tn\n";
            }
        }
    }
}
// purge cache
foreach ($DB->get_records('course') as $c) { if ($c->id!=1) rebuild_course_cache($c->id, true); }
echo "\nNettoyage terminé.\n";
echo "</pre>";
