<?php
/**
 * Script à exécuter UNE SEULE FOIS : masque la section native
 * « Cours disponibles » de la page d'accueil, pour ne garder que le
 * catalogue BEIT (customfrontpageinclude).
 *
 * Accéder à : http://moodle.local/local/beit_frontpage/hide_native_courselist.php
 * (Connecté comme administrateur.)
 *
 * >>> SUPPRIMER ce fichier après exécution. <<<
 *
 * @package   local_beit_frontpage
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

// Sauvegarde des valeurs actuelles (pour pouvoir revenir en arrière).
$oldfp   = get_config('moodle', 'frontpage');
$oldfpli = get_config('moodle', 'frontpageloggedin');

// Vider les deux réglages : aucune section générée par le coeur sur la
// page d'accueil. Seul le customfrontpageinclude (catalogue BEIT) reste.
set_config('frontpage', '');
set_config('frontpageloggedin', '');

echo '<div style="font-family:system-ui,sans-serif;max-width:680px;margin:3rem auto;'
   . 'padding:2rem;border:1px solid #e2e8f0;border-radius:12px;line-height:1.7;">';
echo '<h2 style="margin-top:0;color:#16a34a;">✓ Section native masquée</h2>';
echo '<p>La page d\'accueil n\'affiche plus que le catalogue BEIT.</p>';
echo '<ul>';
echo '<li>Ancien « frontpage » (visiteurs) : <code>' . s($oldfp !== false ? $oldfp : '(vide)') . '</code> → vidé</li>';
echo '<li>Ancien « frontpageloggedin » (connectés) : <code>' . s($oldfpli !== false ? $oldfpli : '(vide)') . '</code> → vidé</li>';
echo '</ul>';
echo '<p style="background:#eff6ff;border:1px solid #bfdbfe;padding:1rem;border-radius:8px;color:#1e40af;">'
   . 'Pour revenir en arrière : Administration du site → Apparence → Page d\'accueil, '
   . 'et recochez « Liste de tous les cours ».</p>';
echo '<p style="background:#fef2f2;border:1px solid #fecaca;padding:1rem;border-radius:8px;color:#b91c1c;">'
   . '<strong>⚠ Important :</strong> supprimez maintenant ce fichier : <code>hide_native_courselist.php</code></p>';
echo '<p><a href="' . (new moodle_url('/'))->out() . '?redirect=0">→ Voir la page d\'accueil</a></p>';
echo '</div>';
