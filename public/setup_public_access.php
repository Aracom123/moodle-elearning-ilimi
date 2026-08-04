<?php
/**
 * Script temporaire — activer l'accès public Moodle.
 * URL : http://moodle.local/public/setup_public_access.php
 * SUPPRIMER après exécution.
 */

require_once(__DIR__ . '/config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

set_config('forcelogin', 0);
set_config('guestloginbutton', 1);
set_config('forceloginforprofiles', 0);
set_config('defaulthomepage', HOMEPAGE_SITE);

// Vider le cache de config
cache_helper::purge_by_definition('core', 'config');

echo '<p style="font-family:sans-serif;padding:2rem;color:green;">
    <strong>✓ Accès public activé.</strong><br><br>
    Paramètres appliqués :<br>
    - forcelogin = 0<br>
    - guestloginbutton = 1<br>
    - defaulthomepage = SITE<br><br>
    <a href="/public/">Tester la page d\'accueil</a><br><br>
    <strong>Pensez à supprimer ce fichier.</strong>
</p>';
