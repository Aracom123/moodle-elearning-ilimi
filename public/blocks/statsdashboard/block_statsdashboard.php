<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Bloc Statistiques de la plateforme.
 *
 * @package    block_statsdashboard
 * @copyright  2026 BAKO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_statsdashboard\local\stats;

/**
 * Affiche les indicateurs globaux de la plateforme pour les administrateurs.
 */
class block_statsdashboard extends block_base {

    /**
     * Initialisation du bloc.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_statsdashboard');
    }

    /**
     * Formats de page autorisés.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true, 'site' => true, 'admin' => true];
    }

    /**
     * Une seule instance par page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Le bloc a une configuration globale ? Non.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Contenu du bloc.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        // Réservé aux administrateurs / gestionnaires.
        if (!has_capability('moodle/site:configview', context_system::instance())) {
            $this->content->text = '';
            return $this->content;
        }

        $cards = [];
        $cards[] = $this->card(get_string('courses', 'block_statsdashboard'),
            (string) stats::count_courses(), '#2e7d32');
        $cards[] = $this->card(get_string('learners', 'block_statsdashboard'),
            (string) stats::count_users_by_archetype(['student']), '#1565c0');
        $cards[] = $this->card(get_string('teachers', 'block_statsdashboard'),
            (string) stats::count_users_by_archetype(['editingteacher', 'teacher']), '#6a1b9a');

        $rate = stats::completion_rate();
        $cards[] = $this->card(get_string('completionrate', 'block_statsdashboard'),
            $rate === null ? get_string('notavailable', 'block_statsdashboard') : $rate . ' %', '#ef6c00');

        $certs = stats::certificates_issued();
        $certvalue = $certs['certificates'] === null
            ? get_string('notavailable', 'block_statsdashboard')
            : (string) $certs['certificates'];
        $cards[] = $this->card(get_string('certificates', 'block_statsdashboard'), $certvalue, '#c62828');
        $cards[] = $this->card(get_string('badges', 'block_statsdashboard'),
            (string) $certs['badges'], '#00838f');

        $avg = stats::average_duration_per_learner();
        $cards[] = $this->card(get_string('avgduration', 'block_statsdashboard'),
            $avg === null ? get_string('notavailable', 'block_statsdashboard') : stats::format_duration($avg),
            '#37474f');

        $html = html_writer::div(implode('', $cards), 'statsdashboard-cards d-flex flex-wrap');

        // Graphique d'activité.
        $activity = stats::activity_by_day();
        $chart = new \core\chart_line();
        $chart->set_smooth(true);
        $chart->set_title(get_string('activitychart', 'block_statsdashboard'));
        $chart->add_series(new \core\chart_series(
            get_string('events', 'block_statsdashboard'), $activity['events']));
        $chart->add_series(new \core\chart_series(
            get_string('activeusers', 'block_statsdashboard'), $activity['users']));
        $chart->set_labels($activity['labels']);
        $html .= html_writer::div($OUTPUT->render($chart), 'statsdashboard-chart mt-3');

        $this->content->text = $html;
        return $this->content;
    }

    /**
     * Génère une carte indicateur.
     *
     * @param string $label Libellé.
     * @param string $value Valeur affichée.
     * @param string $color Couleur d'accent (hex).
     * @return string HTML
     */
    protected function card(string $label, string $value, string $color): string {
        $style = 'flex:1 1 140px;margin:4px;padding:12px;border-radius:8px;'
            . 'background:#f8f9fa;border-left:4px solid ' . $color . ';min-width:140px;';
        $valuehtml = html_writer::div(s($value), 'h3 mb-0', ['style' => 'color:' . $color]);
        $labelhtml = html_writer::div(s($label), 'small text-muted');
        return html_writer::div($valuehtml . $labelhtml, '', ['style' => $style]);
    }
}
