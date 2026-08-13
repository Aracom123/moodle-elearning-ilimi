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

    // ── Accueil ISP : une carte de terrain pédagogique ─────────────────
    $scss .= '
body.pagelayout-frontpage { background: #f4f7f6; }
.navbar-brand .logo { display: none !important; }
.navbar-brand::after {
    content: "ISP eLearning";
    color: #fff;
    font-weight: 700;
    letter-spacing: -.01em;
}
body.pagelayout-frontpage #page.drawers .main-inner {
    max-width: 1440px;
    padding-left: clamp(1rem, 4vw, 4rem);
    padding-right: clamp(1rem, 4vw, 4rem);
}
.isp-home-hero {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
    gap: clamp(2.5rem, 6vw, 7rem);
    align-items: center;
    min-height: 590px;
    margin: 1.5rem 0 4.5rem;
    padding: clamp(2rem, 5vw, 5.5rem);
    overflow: hidden;
    border-radius: 2px 48px 2px 48px;
    color: #fff;
    background: #073b55;
    box-shadow: 0 28px 70px rgba(4, 43, 63, .2);
}
.isp-home-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at 82% 16%, rgba(20, 184, 166, .22), transparent 30%),
        linear-gradient(115deg, transparent 55%, rgba(255,255,255,.025) 55%);
}
.isp-home-copy, .isp-field-map { position: relative; z-index: 1; }
.isp-home-eyebrow {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: 0 0 1.5rem;
    color: #a9ddd7;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
}
.isp-home-eyebrow span { width: 2.5rem; height: 2px; background: #f6b73f; }
.isp-home-copy h1 {
    max-width: 760px;
    margin: 0;
    color: #fff;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(3rem, 5.4vw, 5.8rem);
    font-weight: 500;
    line-height: .98;
    letter-spacing: -.045em;
}
.isp-home-lead {
    max-width: 640px;
    margin: 2rem 0 0;
    color: rgba(255,255,255,.74);
    font-size: clamp(1rem, 1.35vw, 1.22rem);
    line-height: 1.75;
}
.isp-home-actions { display: flex; align-items: center; gap: 1.5rem; margin-top: 2.4rem; flex-wrap: wrap; }
.isp-btn-primary {
    padding: .9rem 1.4rem;
    border: 1px solid #f6b73f;
    border-radius: 0 14px 0 14px;
    background: #f6b73f;
    color: #17324a !important;
    font-weight: 750;
    box-shadow: none;
}
.isp-btn-primary:hover, .isp-btn-primary:focus {
    border-color: #ffd780;
    background: #ffd780;
    color: #102a43 !important;
    transform: translateY(-2px);
}
.isp-link-action { color: #fff !important; font-weight: 650; text-decoration: underline; text-underline-offset: .38rem; }
.isp-link-action:hover { color: #a9ddd7 !important; }
.isp-home-facts {
    display: flex;
    gap: 1.4rem;
    margin: 3rem 0 0;
    padding: 1.25rem 0 0;
    border-top: 1px solid rgba(255,255,255,.15);
    list-style: none;
    color: rgba(255,255,255,.66);
    font-size: .78rem;
    letter-spacing: .03em;
}
.isp-home-facts li + li { padding-left: 1.4rem; border-left: 1px solid rgba(255,255,255,.15); }
.isp-home-facts strong { color: #fff; font-size: 1rem; }
.isp-field-map {
    min-height: 430px;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 2px 34px 2px 34px;
    background: rgba(4, 45, 65, .72);
    backdrop-filter: blur(6px);
}
.isp-map-grid {
    position: absolute; inset: 0; opacity: .13;
    background-image: linear-gradient(rgba(255,255,255,.45) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.45) 1px, transparent 1px);
    background-size: 34px 34px;
}
.isp-map-label { position: absolute; top: 1.5rem; left: 1.6rem; margin: 0; color: #a9ddd7; font-size: .68rem; letter-spacing: .14em; }
.isp-map-path { position: absolute; inset: 60px 18px 62px; }
.isp-map-path svg { width: 100%; height: 100%; overflow: visible; }
.isp-map-line { fill: none; stroke: #f6b73f; stroke-width: 3; stroke-dasharray: 8 8; }
.isp-map-path circle { fill: #f6b73f; stroke: #073b55; stroke-width: 5; }
.isp-map-step { position: absolute; display: flex; flex-direction: column; color: #fff; }
.isp-map-step span { color: #f6b73f; font: italic 1rem Georgia, serif; }
.isp-map-step strong { font: 500 clamp(1.2rem, 2vw, 1.65rem) Georgia, serif; }
.isp-map-step small { color: rgba(255,255,255,.6); }
.isp-map-step--one { left: 5%; bottom: 24%; }
.isp-map-step--two { left: 43%; top: 25%; }
.isp-map-step--three { right: 4%; bottom: 24%; text-align: right; }
.isp-announcement-link {
    position: absolute; right: 1.6rem; bottom: 1.35rem;
    color: #bde9e4 !important; font-size: .78rem; text-decoration: none;
}
.isp-announcement-link span { color: #f6b73f; margin-right: .35rem; }
body.pagelayout-frontpage #page-content {
    padding: clamp(1.5rem, 3vw, 3rem) !important;
    border: 1px solid #dce7e4;
    border-radius: 2px 30px 2px 30px;
    background: #fff;
    box-shadow: 0 14px 38px rgba(17, 61, 70, .07);
}
body.pagelayout-frontpage #page-content h2 {
    color: #123f52;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.6rem, 2.4vw, 2.35rem);
    font-weight: 500;
}
body.pagelayout-frontpage .coursebox {
    margin: .8rem 0;
    padding: 1rem 1.2rem;
    border: 0;
    border-left: 3px solid #15958a;
    border-radius: 0 12px 12px 0;
    background: #f6faf9;
    transition: transform .16s ease, background .16s ease;
}
body.pagelayout-frontpage .coursebox:hover { transform: translateX(4px); background: #edf7f5; }
body.pagelayout-frontpage .coursebox .coursename a { color: #123f52; font-weight: 700; }
body.pagelayout-frontpage .forumpost { border-color: #dce7e4; box-shadow: none; }
@media (max-width: 1050px) {
    .isp-home-hero { grid-template-columns: 1fr; }
    .isp-field-map { min-height: 390px; }
}
@media (max-width: 650px) {
    .isp-home-hero { min-height: auto; margin-top: .75rem; padding: 2.1rem 1.3rem; border-radius: 2px 28px 2px 28px; }
    .isp-home-copy h1 { font-size: clamp(2.65rem, 14vw, 4rem); }
    .isp-home-facts { gap: .75rem; flex-wrap: wrap; }
    .isp-home-facts li + li { padding-left: .75rem; }
    .isp-field-map { min-height: 350px; }
    .isp-map-step strong { font-size: 1.05rem; }
    .isp-map-step small { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    .isp-btn-primary, body.pagelayout-frontpage .coursebox { transition: none; }
}
';

    return $scss;
}
