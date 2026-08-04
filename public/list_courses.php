<?php
/**
 * Script de diagnostic — liste tous les cours
 * À supprimer après usage.
 */
require_once('../config.php');
require_login();
if (!is_siteadmin()) die('Accès refusé.');

header('Content-Type: text/html; charset=utf-8');

echo "<style>body{font-family:Arial;max-width:900px;margin:40px auto}
table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:8px}
th{background:#1F3A5F;color:#fff}tr:nth-child(even){background:#f4f7fa}
code{background:#eee;padding:2px 6px;border-radius:3px}</style>";

echo "<h2>Liste des cours</h2>";

$courses = $DB->get_records('course', null, 'sortorder ASC', 'id, shortname, fullname, category, visible');

echo "<p>Total : <strong>" . (count($courses) - 1) . "</strong> cours (hors site principal)</p>";
echo "<table><tr><th>ID</th><th>Nom abrégé</th><th>Nom complet</th><th>Catégorie</th><th>Visible</th><th>Activités</th></tr>";

foreach ($courses as $c) {
    if ($c->id == 1) continue; // site principal
    $nbmods = $DB->count_records('course_modules', ['course' => $c->id]);
    $vis = $c->visible ? 'Oui' : 'Non';
    echo "<tr><td>{$c->id}</td><td><code>" . s($c->shortname) . "</code></td><td>"
       . s($c->fullname) . "</td><td>{$c->category}</td><td>{$vis}</td><td>{$nbmods}</td></tr>";
}
echo "</table>";

// Version texte facile à copier
echo "<h3>Version copiable (shortname | fullname | nb activités)</h3><pre style='background:#222;color:#0f0;padding:15px'>";
foreach ($courses as $c) {
    if ($c->id == 1) continue;
    $nbmods = $DB->count_records('course_modules', ['course' => $c->id]);
    echo $c->id . " | " . $c->shortname . " | " . $c->fullname . " | " . $nbmods . " activités\n";
}
echo "</pre>";
