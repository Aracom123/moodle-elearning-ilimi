<?php
/**
 * Bibliothèque de fonctions du plugin BEIT Frontpage.
 *
 * @package   local_beit_frontpage
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Récupère tous les cours visibles (hors cours du site), enrichis pour le filtrage.
 *
 * Chaque cours reçoit : ->categoryname, ->timecreated, ->canenrol (bool).
 *
 * @return array Tableau d'objets cours.
 */
function local_beit_frontpage_get_courses() {
    global $DB;

    $courses = get_courses(
        'all',
        'c.sortorder ASC',
        'c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.visible, c.category, c.timecreated'
    );
    unset($courses[SITEID]);
    $courses = array_filter($courses, function($c) {
        return $c->visible == 1;
    });

    if (empty($courses)) {
        return [];
    }

    // Noms de catégories en une seule requête.
    $catids = array_unique(array_map(function($c) {
        return $c->category;
    }, $courses));
    $categories = [];
    if (!empty($catids)) {
        list($insql, $params) = $DB->get_in_or_equal($catids);
        $records = $DB->get_records_select('course_categories', "id $insql", $params, '', 'id, name, visible');
        foreach ($records as $cat) {
            $categories[$cat->id] = $cat;
        }
    }

    foreach ($courses as $course) {
        $cat = isset($categories[$course->category]) ? $categories[$course->category] : null;
        $course->categoryname = $cat ? format_string($cat->name) : '';
        // Le cours est-il ouvert à l'auto-inscription / inscription possible ?
        $course->canenrol = local_beit_frontpage_course_can_enrol($course->id);
    }

    return $courses;
}

/**
 * Indique si un cours propose au moins une méthode d'inscription active
 * permettant à un nouvel utilisateur de s'inscrire (ex. self enrolment).
 *
 * @param int $courseid
 * @return bool
 */
function local_beit_frontpage_course_can_enrol($courseid) {
    global $DB;
    // "Disponible à l'inscription" = au moins une méthode self/guest active (status = 0 = activée).
    $sql = "SELECT COUNT(*) FROM {enrol}
             WHERE courseid = ? AND status = 0 AND enrol IN ('self', 'guest')";
    return $DB->count_records_sql($sql, [$courseid]) > 0;
}

/**
 * Récupère l'URL de l'image (overview) d'un cours, ou chaîne vide.
 *
 * @param int $courseid
 * @return string
 */
function local_beit_frontpage_course_image($courseid) {
    $context = context_course::instance($courseid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
    foreach ($files as $file) {
        if ($file->is_valid_image()) {
            return moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                null,
                $file->get_filepath(),
                $file->get_filename()
            )->out();
        }
    }
    return '';
}

/**
 * Construit un résumé court et nettoyé du cours.
 *
 * @param object $course
 * @param int $maxlen
 * @return string
 */
function local_beit_frontpage_short_summary($course, $maxlen = 120) {
    $format = isset($course->summaryformat) ? $course->summaryformat : FORMAT_HTML;
    $summary = strip_tags(format_text($course->summary, $format));
    $summary = trim($summary);
    if (core_text::strlen($summary) > $maxlen) {
        $summary = core_text::substr($summary, 0, $maxlen) . '…';
    }
    return $summary;
}

/**
 * Rend une carte de cours, avec attributs data-* pour le filtrage côté client.
 *
 * @param object $course
 * @param bool $isloggedin Utilisateur connecté (et non invité).
 * @return string HTML
 */
function local_beit_frontpage_render_card($course, $isloggedin) {
    $courseimage = local_beit_frontpage_course_image($course->id);
    $summary = local_beit_frontpage_short_summary($course);

    if ($isloggedin) {
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $btnlabel = get_string('accesscourse', 'local_beit_frontpage');
        $btnclass = 'beit-course-btn';
    } else {
        $url = new moodle_url('/local/beit_frontpage/preview.php', ['id' => $course->id]);
        $btnlabel = get_string('viewcourse', 'local_beit_frontpage');
        $btnclass = 'beit-course-btn beit-course-btn--guest';
    }

    // Données pour le filtrage / tri JS.
    $catname = isset($course->categoryname) ? $course->categoryname : '';
    $timecreated = isset($course->timecreated) ? (int)$course->timecreated : 0;
    $canenrol = !empty($course->canenrol) ? '1' : '0';
    // Texte de recherche concaténé (minuscule).
    $searchblob = core_text::strtolower(
        $course->fullname . ' ' . $course->shortname . ' ' . $catname . ' ' . $summary
    );

    $data = ' data-category="' . s($catname) . '"'
          . ' data-timecreated="' . $timecreated . '"'
          . ' data-canenrol="' . $canenrol . '"'
          . ' data-name="' . s(core_text::strtolower($course->fullname)) . '"'
          . ' data-search="' . s($searchblob) . '"';

    $out  = '<div class="beit-course-card"' . $data . '>';
    $out .= '<a href="' . $url->out() . '" class="beit-course-card-link">';
    $out .= '<div class="beit-course-img">';
    if ($courseimage) {
        $out .= '<img src="' . $courseimage . '" alt="' . s($course->fullname) . '" loading="lazy">';
    } else {
        $out .= '<div class="beit-course-img-placeholder"><i class="fa fa-graduation-cap" aria-hidden="true"></i></div>';
    }
    $out .= '</div>';
    $out .= '<div class="beit-course-body">';
    if ($catname !== '') {
        $out .= '<span class="beit-course-shortname">' . s($catname) . '</span>';
    } else {
        $out .= '<span class="beit-course-shortname">' . s($course->shortname) . '</span>';
    }
    $out .= '<h3 class="beit-course-name">' . s($course->fullname) . '</h3>';
    if ($summary) {
        $out .= '<p class="beit-course-summary">' . s($summary) . '</p>';
    }
    $out .= '</div>';
    $out .= '<div class="beit-course-footer">';
    $out .= '<span class="' . $btnclass . '">' . $btnlabel . '</span>';
    $out .= '</div>';
    $out .= '</a>';
    $out .= '</div>';
    return $out;
}

/**
 * Renvoie le CSS du catalogue dans une balise <style>, une seule fois.
 *
 * @return string
 */
function local_beit_frontpage_inline_css() {
    static $printed = false;
    if ($printed) {
        return '';
    }
    $printed = true;
    $cssfile = __DIR__ . '/styles.css';
    if (!is_readable($cssfile)) {
        return '';
    }
    return '<style>' . "\n" . file_get_contents($cssfile) . "\n" . '</style>';
}

/**
 * Renvoie le JavaScript de filtrage côté client, une seule fois.
 *
 * @return string
 */
function local_beit_frontpage_inline_js() {
    static $printed = false;
    if ($printed) {
        return '';
    }
    $printed = true;
    $jsfile = __DIR__ . '/filter.js';
    if (!is_readable($jsfile)) {
        return '';
    }
    return '<script>' . "\n" . file_get_contents($jsfile) . "\n" . '</script>';
}

/**
 * Construit la liste des catégories distinctes présentes dans les cours.
 *
 * @param array $courses
 * @return array [name => count]
 */
function local_beit_frontpage_collect_categories($courses) {
    $cats = [];
    foreach ($courses as $course) {
        $name = isset($course->categoryname) ? $course->categoryname : '';
        if ($name === '') {
            continue;
        }
        if (!isset($cats[$name])) {
            $cats[$name] = 0;
        }
        $cats[$name]++;
    }
    ksort($cats, SORT_NATURAL | SORT_FLAG_CASE);
    return $cats;
}

/**
 * Rend la colonne de filtres.
 *
 * @param array $courses
 * @return string HTML
 */
function local_beit_frontpage_render_filters($courses) {
    $categories = local_beit_frontpage_collect_categories($courses);

    $out  = '<aside class="beit-filters" id="beit-filters">';
    $out .= '<div class="beit-filters-header">';
    $out .= '<h2 class="beit-filters-title">' . get_string('filters', 'local_beit_frontpage') . '</h2>';
    $out .= '<button type="button" class="beit-filters-reset" id="beit-reset">'
          . get_string('resetfilters', 'local_beit_frontpage') . '</button>';
    $out .= '</div>';

    // Recherche.
    $out .= '<div class="beit-filter-group">';
    $out .= '<label class="beit-filter-label" for="beit-search">'
          . get_string('searchlabel', 'local_beit_frontpage') . '</label>';
    $out .= '<input type="search" id="beit-search" class="beit-filter-search" '
          . 'placeholder="' . s(get_string('searchplaceholder', 'local_beit_frontpage')) . '" autocomplete="off">';
    $out .= '</div>';

    // Tri.
    $out .= '<div class="beit-filter-group">';
    $out .= '<label class="beit-filter-label" for="beit-sort">'
          . get_string('sortlabel', 'local_beit_frontpage') . '</label>';
    $out .= '<select id="beit-sort" class="beit-filter-select">';
    $out .= '<option value="default">' . get_string('sortdefault', 'local_beit_frontpage') . '</option>';
    $out .= '<option value="az">' . get_string('sortaz', 'local_beit_frontpage') . '</option>';
    $out .= '<option value="za">' . get_string('sortza', 'local_beit_frontpage') . '</option>';
    $out .= '<option value="recent">' . get_string('sortrecent', 'local_beit_frontpage') . '</option>';
    $out .= '</select>';
    $out .= '</div>';

    // Disponibilité.
    $out .= '<div class="beit-filter-group">';
    $out .= '<span class="beit-filter-label">' . get_string('availabilitylabel', 'local_beit_frontpage') . '</span>';
    $out .= '<label class="beit-filter-check"><input type="checkbox" id="beit-enrol-only" value="1"> '
          . get_string('enrolonly', 'local_beit_frontpage') . '</label>';
    $out .= '</div>';

    // Catégories.
    if (!empty($categories)) {
        $out .= '<div class="beit-filter-group">';
        $out .= '<span class="beit-filter-label">' . get_string('categorylabel', 'local_beit_frontpage') . '</span>';
        $out .= '<div class="beit-filter-cats">';
        foreach ($categories as $name => $count) {
            $out .= '<label class="beit-filter-check">'
                  . '<input type="checkbox" class="beit-cat-check" value="' . s($name) . '"> '
                  . s($name) . ' <span class="beit-cat-count">(' . $count . ')</span>'
                  . '</label>';
        }
        $out .= '</div>';
        $out .= '</div>';
    }

    $out .= '</aside>';
    return $out;
}

/**
 * Rend le catalogue complet : colonne filtres + grille de cours, pleine largeur.
 *
 * @param array $courses
 * @param bool $isloggedin
 * @return string HTML
 */
function local_beit_frontpage_render_catalog($courses, $isloggedin) {
    $out = local_beit_frontpage_inline_css();
    $out .= '<div class="beit-catalog beit-catalog--full">';

    // Hero.
    $out .= '<div class="beit-catalog-hero">';
    $out .= '<h1 class="beit-catalog-title">' . get_string('catalogtitle', 'local_beit_frontpage') . '</h1>';
    $out .= '<p class="beit-catalog-subtitle">' . get_string('catalogsubtitle', 'local_beit_frontpage') . '</p>';
    if (!$isloggedin) {
        $loginurl = new moodle_url('/login/index.php');
        $out .= '<a href="' . $loginurl->out() . '" class="beit-catalog-cta">'
              . get_string('logintoenrol', 'local_beit_frontpage') . '</a>';
    }
    $out .= '</div>';

    if (empty($courses)) {
        $out .= '<div class="beit-catalog-empty"><p>'
              . get_string('nocourses', 'local_beit_frontpage') . '</p></div>';
        $out .= '</div>';
        return $out;
    }

    // Layout deux colonnes : filtres + cours.
    $out .= '<div class="beit-layout">';

    // Colonne filtres.
    $out .= local_beit_frontpage_render_filters($courses);

    // Colonne cours.
    $out .= '<div class="beit-results">';
    $out .= '<div class="beit-results-bar">';
    $singular = get_string('coursefound', 'local_beit_frontpage');
    $plural = get_string('coursesfound', 'local_beit_frontpage');
    $count = count($courses);
    $word = ($count === 1) ? $singular : $plural;
    $out .= '<span class="beit-results-count" id="beit-count"'
          . ' data-singular="' . s($singular) . '" data-plural="' . s($plural) . '">'
          . $count . ' ' . $word . '</span>';
    $out .= '<button type="button" class="beit-filters-toggle" id="beit-filters-toggle">'
          . get_string('filters', 'local_beit_frontpage') . '</button>';
    $out .= '</div>';

    $out .= '<div class="beit-courses-grid" id="beit-grid">';
    foreach ($courses as $course) {
        $out .= local_beit_frontpage_render_card($course, $isloggedin);
    }
    $out .= '</div>';

    // État vide (résultats filtrés nuls).
    $out .= '<div class="beit-no-results" id="beit-no-results" hidden><p>'
          . get_string('nomatch', 'local_beit_frontpage') . '</p></div>';

    $out .= '</div>'; // .beit-results
    $out .= '</div>'; // .beit-layout
    $out .= '</div>'; // .beit-catalog

    $out .= local_beit_frontpage_inline_js();
    return $out;
}
