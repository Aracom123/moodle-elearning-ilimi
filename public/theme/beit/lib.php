<?php
/**
 * BEIT theme lib.
 *
 * @package   theme_beit
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content.
 */
function theme_beit_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($filename == 'default.scss' || empty($filename)) {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_beit', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    return $scss;
}

/**
 * Get compiled css.
 */
function theme_beit_get_precompiled_css() {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/style/moodle.css');
}

/**
 * Inject SCSS variables before compilation (couleurs BEIT).
 */
function theme_beit_get_pre_scss($theme) {
    global $CFG;

    $scss = '';
    $scss .= '$primary: #005489;' . "\n";
    $scss .= '$secondary: #FF4500;' . "\n";
    $scss .= '$navbar-dark-bg: #005489;' . "\n";
    $scss .= '$drawer-bg-color: #005489;' . "\n";

    if (!empty($theme->settings->brandcolor)) {
        $scss .= '$primary: ' . $theme->settings->brandcolor . ';' . "\n";
    }

    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Inject extra SCSS after compilation.
 */
function theme_beit_get_extra_scss($theme) {
    $scss = '';

    // ── Couleurs et navbar ──────────────────────────────────────────────
    $scss .= '
/* BEIT Custom Theme */
:root {
    --beit-primary: #005489;
    --beit-secondary: #FF4500;
}

/* Empêcher le scroll horizontal global */
html,
body,
#page-wrapper {
    overflow-x: hidden !important;
    max-width: 100% !important;
}

/* Centrage de la page login */
body.path-login,
body.path-login #page-wrapper {
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Navbar */
.navbar {
    background-color: #005489 !important;
}

/* Textes et icônes de la navbar */
.navbar .nav-link,
.navbar .navbar-brand,
.navbar .nav-link span,
.navbar .nav-link .fa,
.navbar .userinitials,
.navbar [data-region="usermenu-container"] .nav-link,
.primary-navigation .nav-link,
.primary-navigation .nav-link span {
    color: #ffffff !important;
}

.navbar .nav-link:hover,
.navbar .nav-link:focus,
.primary-navigation .nav-link:hover {
    color: #FFD700 !important;
    background-color: rgba(255,255,255,0.1) !important;
    border-radius: 6px;
}

.navbar-toggler-icon,
.navbar .navbar-toggler {
    color: #fff !important;
    border-color: rgba(255,255,255,0.5) !important;
}

.navbar .icon,
.navbar svg {
    fill: #ffffff !important;
    color: #ffffff !important;
}

/* Drawer / sidebar */
#nav-drawer {
    background-color: #005489 !important;
    color: #fff;
}
#nav-drawer a,
#nav-drawer .nav-link {
    color: rgba(255,255,255,0.85) !important;
}
#nav-drawer a:hover,
#nav-drawer .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255,255,255,0.1) !important;
}

/* Boutons primaires */
.btn-primary {
    background-color: #005489 !important;
    border-color: #005489 !important;
}
.btn-primary:hover {
    background-color: #003d66 !important;
    border-color: #003d66 !important;
}

/* Liens */
a {
    color: #005489;
}
a:hover {
    color: #FF4500;
}

/* Accents secondaires */
.nav-link.active,
.nav-tabs .nav-item.show .nav-link,
.nav-tabs .nav-link.active {
    color: #FF4500 !important;
    border-bottom-color: #FF4500 !important;
}

/* Header du site */
.site-header,
header[role="banner"] {
    background-color: #005489;
}
';

    // Extra SCSS depuis les settings
    if (!empty($theme->settings->scss)) {
        $scss .= $theme->settings->scss;
    }

    // ── Page de login ───────────────────────────────────────────────────
    $scss .= '
/* Fond pleine page */
.beit-login-bg {
    min-height: 100vh;
    width: 100%;
    background: linear-gradient(135deg, #005489 0%, #003d66 60%, #00264d 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    box-sizing: border-box;
}

/* Masquer le logo et le titre injectés par Moodle sur la page login */
body.path-login #loginlogo,
body.path-login .login-logo,
body.path-login #logoimage,
body.path-login .loginform h1.login-heading,
body.path-login h1.login-heading {
    display: none !important;
}

/* Carte formulaire centree */
.beit-login-card {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);
    padding: 2.5rem 2rem;
}

.beit-login-card-header {
    text-align: center;
    margin-bottom: 1.75rem;
}

.beit-login-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #005489, #0077bb);
    margin-bottom: 1rem;
    box-shadow: 0 6px 20px rgba(0, 84, 137, 0.35);
}

.beit-login-icon .fa {
    font-size: 1.75rem;
    color: #fff;
}

.beit-login-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a2e45;
    margin: 0 0 0.4rem;
}

.beit-login-subtitle {
    font-size: 0.82rem;
    color: #6b7a8d;
    margin: 0;
}

.beit-login-card .login-form .form-control {
    border-radius: 10px;
    border: 1.5px solid #d1dce8;
    padding: 0.7rem 1rem;
    font-size: 0.9rem;
    background-color: #f7f9fc;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.beit-login-card .login-form .form-control:focus {
    border-color: #005489;
    box-shadow: 0 0 0 3px rgba(0, 84, 137, 0.12);
    background-color: #fff;
    outline: none;
}

.beit-login-card .login-form .btn-primary {
    width: 100%;
    padding: 0.8rem;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    background: linear-gradient(135deg, #005489 0%, #0077bb 100%) !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(0, 84, 137, 0.3);
    transition: transform 0.15s, box-shadow 0.15s;
}

.beit-login-card .login-form .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 84, 137, 0.45) !important;
}

.beit-login-card .login-form-forgotpassword a {
    color: #005489;
    font-size: 0.82rem;
}

.beit-login-card .login-form-forgotpassword a:hover {
    color: #FF4500;
    text-decoration: underline;
}

.beit-login-card .loginform {
    color: #333;
}

.beit-login-card .btn-secondary {
    font-size: 0.78rem;
    border-radius: 8px;
}

';

    // ── Catalogue de cours (frontpage) ─────────────────────────────────
    $scss .= '
.beit-catalog {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem 3rem;
}

.beit-catalog-hero {
    text-align: center;
    padding: 3rem 1rem 2.5rem;
}

.beit-catalog-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a2e45;
    margin-bottom: .75rem;
}

.beit-catalog-subtitle {
    font-size: 1rem;
    color: #6b7a8d;
    margin-bottom: 1.5rem;
}

.beit-catalog-cta {
    display: inline-block;
    background: #005489;
    color: #fff !important;
    padding: .75rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none !important;
    transition: background 0.2s, transform 0.15s;
}

.beit-catalog-cta:hover {
    background: #003d66;
    transform: translateY(-2px);
    color: #fff !important;
}

.beit-catalog-empty {
    text-align: center;
    padding: 3rem;
    color: #6b7a8d;
}

/* Grille des cours */
.beit-courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.beit-course-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}

.beit-course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 84, 137, 0.15);
}

.beit-course-card-link {
    display: flex;
    flex-direction: column;
    height: 100%;
    text-decoration: none !important;
    color: inherit !important;
}

.beit-course-img {
    width: 100%;
    height: 170px;
    overflow: hidden;
    background: linear-gradient(135deg, #005489, #003d66);
}

.beit-course-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.beit-course-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.beit-course-img-placeholder .fa {
    font-size: 3rem;
    color: rgba(255,255,255,0.4);
}

.beit-course-body {
    padding: 1.25rem 1.25rem .75rem;
    flex: 1;
}

.beit-course-shortname {
    display: inline-block;
    background: #e8f1f8;
    color: #005489;
    font-size: 0.72rem;
    font-weight: 600;
    padding: .2rem .6rem;
    border-radius: 20px;
    margin-bottom: .6rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.beit-course-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1a2e45;
    margin: 0 0 .5rem;
    line-height: 1.4;
}

.beit-course-summary {
    font-size: 0.82rem;
    color: #6b7a8d;
    line-height: 1.5;
    margin: 0;
}

.beit-course-footer {
    padding: .75rem 1.25rem 1.25rem;
}

.beit-course-btn {
    display: inline-block;
    background: #005489;
    color: #fff;
    font-size: 0.82rem;
    font-weight: 600;
    padding: .5rem 1.1rem;
    border-radius: 8px;
    transition: background 0.15s;
}

.beit-course-btn--guest {
    background: transparent;
    color: #005489;
    border: 1.5px solid #005489;
}

.beit-course-card:hover .beit-course-btn {
    background: #003d66;
}

.beit-course-card:hover .beit-course-btn--guest {
    background: #005489;
    color: #fff;
}
';

    // ── Footer du site ──────────────────────────────────────────────────
    $scss .= '
.beit-site-footer,
.beit-site-footer *,
.beit-footer-inner,
.beit-footer-bottom {
    box-sizing: border-box;
}

.beit-site-footer {
    background: #005489;
    color: rgba(255,255,255,0.8);
    font-size: 0.875rem;
    padding: 0;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    /* #page-wrapper est en display:flex ; sans ceci le footer (item flex)
       est écrasé à hauteur 0 par flex-shrink. */
    flex-shrink: 0;
}

.beit-footer-inner {
    display: flex;
    gap: 2rem;
    padding: 2.5rem 2rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    flex-wrap: wrap;
}

.beit-footer-col {
    flex: 1;
    min-width: 180px;
}

.beit-footer-brand {
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: .5rem;
}

.beit-footer-desc {
    color: rgba(255,255,255,0.6);
    line-height: 1.6;
    font-size: 0.82rem;
}

.beit-footer-heading {
    font-weight: 600;
    color: #fff;
    margin-bottom: .75rem;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.beit-footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.beit-footer-links li {
    margin-bottom: .4rem;
}

.beit-footer-links a,
.beit-footer-links .footer-support-link a {
    color: rgba(255,255,255,0.65) !important;
    text-decoration: none;
    transition: color 0.15s;
}

.beit-footer-links a:hover,
.beit-footer-links .footer-support-link a:hover {
    color: #FFD700 !important;
}

.beit-footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    font-size: 0.78rem;
    color: rgba(255,255,255,0.45);
    flex-wrap: wrap;
    gap: .5rem;
}

.beit-footer-bottom a {
    color: rgba(255,255,255,0.65) !important;
    text-decoration: none;
}

.beit-footer-bottom a:hover {
    color: #FFD700 !important;
}
';

    // ── Index des sections du cours (course index drawer) ───────────────
    // Fond teal foncé + texte clair, page active en accent doré.
    // Volontairement distinct de la navbar (#005489) et du footer.
    $scss .= '
/* Colonne index des sections : fond teal foncé */
.drawer.drawer-left {
    background-color: #0E4F4F !important;
}
#courseindex,
.courseindex,
[data-region="courseindex"] {
    background-color: #0E4F4F !important;
}

/* En-tete du tiroir (titre du cours + icones) */
.drawer.drawer-left .drawerheader,
#courseindexcollapse,
.drawer.drawer-left [data-region="drawertoggle"] {
    background-color: #0E4F4F !important;
}
.drawer.drawer-left .drawerheader,
.drawer.drawer-left .drawerheader a,
.drawer.drawer-left .drawerheader span,
.drawer.drawer-left .drawerheader .btn {
    color: #FFFFFF !important;
}

/* Titres de sections : blanc gras */
.courseindex .courseindex-section .courseindex-link,
.courseindex .courseindex-sectiontitle,
.courseindex .courseindex-section > .courseindex-item .courseindex-link,
.courseindex-section-title {
    color: #FFFFFF !important;
    font-weight: 600 !important;
}

/* Liens d activites : bleu-vert clair */
.courseindex .courseindex-item .courseindex-link,
.courseindex .courseindex-link,
.courseindex .courseindex-item a {
    color: #CDEAEA !important;
}

/* Chevrons / icones de pliage */
.courseindex .icons-collapse-expand,
.courseindex .icons-collapse-expand .expanded-icon,
.courseindex .icons-collapse-expand .collapsed-icon,
.courseindex i,
.courseindex .courseindex-chevron {
    color: #9FD6D6 !important;
}

/* Survol */
.courseindex .courseindex-item:hover,
.courseindex .courseindex-link:hover {
    background-color: #136B6B !important;
    border-radius: 4px;
}
.courseindex .courseindex-item:hover .courseindex-link,
.courseindex .courseindex-link:hover {
    color: #FFFFFF !important;
}

/* Page / activite active : accent dore */
.courseindex .courseindex-item.pageitem.active,
.courseindex .courseindex-item.active,
.courseindex .pageitem.active {
    background-color: #E9C46A !important;
    border-radius: 4px;
}
.courseindex .courseindex-item.pageitem.active .courseindex-link,
.courseindex .courseindex-item.active .courseindex-link,
.courseindex .pageitem.active .courseindex-link {
    color: #0E4F4F !important;
    font-weight: 700 !important;
}

/* Indicateurs / puces lisibles sur fond fonce */
.courseindex .courseindex-item .completioninfo,
.courseindex .pageitem .courseindex-link::before {
    color: #9FD6D6 !important;
}

/* Barre de defilement du tiroir */
.drawer.drawer-left::-webkit-scrollbar { width: 8px; }
.drawer.drawer-left::-webkit-scrollbar-track { background: #0E4F4F; }
.drawer.drawer-left::-webkit-scrollbar-thumb { background: #136B6B; border-radius: 4px; }
';

    // ── Boutons de navigation Precedent / Suivant ───────────────────────
    $scss .= '
.beit-activity-nav {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    gap: 1rem;
    margin: 2.5rem 0 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e3e8ee;
    flex-wrap: wrap;
}
.beit-activity-nav .beit-nav-prev,
.beit-activity-nav .beit-nav-next {
    flex: 1 1 0;
    min-width: 200px;
    display: flex;
}
.beit-activity-nav .beit-nav-next { justify-content: flex-end; }

.beit-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: .75rem;
    max-width: 100%;
    padding: .75rem 1.25rem;
    border-radius: 10px;
    background: #ffffff;
    border: 1.5px solid #005489;
    color: #005489 !important;
    text-decoration: none !important;
    transition: background .15s, color .15s, transform .15s, box-shadow .15s;
    box-shadow: 0 2px 8px rgba(0,84,137,.08);
}
.beit-nav-btn:hover {
    background: #005489;
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,84,137,.28);
}
.beit-nav-btn--next { flex-direction: row; text-align: right; }
.beit-nav-arrow { font-size: 1.4rem; line-height: 1; font-weight: 700; }
.beit-nav-text { display: flex; flex-direction: column; overflow: hidden; }
.beit-nav-btn--next .beit-nav-text { align-items: flex-end; }
.beit-nav-label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .75;
    font-weight: 600;
}
.beit-nav-title {
    font-size: .92rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 240px;
}
@media (max-width: 576px) {
    .beit-activity-nav { flex-direction: column; }
    .beit-activity-nav .beit-nav-next { justify-content: flex-start; }
    .beit-nav-btn--next { flex-direction: row-reverse; text-align: left; }
    .beit-nav-btn--next .beit-nav-text { align-items: flex-start; }
    .beit-nav-title { max-width: 70vw; }
}
';

    return $scss;
}
