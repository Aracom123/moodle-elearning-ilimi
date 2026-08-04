<?php
/**
 * BEIT theme settings.
 *
 * @package   theme_beit
 * @copyright 2026 BEIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('theme_beit', get_string('configtitle', 'theme_beit'));
    $page = new admin_settingpage('theme_beit_general', get_string('generalsettings', 'theme_boost'));

    // Preset
    $name = 'theme_beit/preset';
    $title = get_string('preset', 'theme_boost');
    $description = get_string('preset_desc', 'theme_boost');
    $default = 'default.scss';
    $choices = ['default.scss' => 'default.scss', 'plain.scss' => 'plain.scss'];
    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'boost');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Brand color (override)
    $name = 'theme_beit/brandcolor';
    $title = get_string('brandcolor', 'theme_boost');
    $description = get_string('brandcolor_desc', 'theme_boost');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#005489');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // Advanced settings
    $page = new admin_settingpage('theme_beit_advanced', get_string('advancedsettings', 'theme_boost'));

    // Raw SCSS pre
    $name = 'theme_beit/scsspre';
    $title = get_string('rawscsspre', 'theme_boost');
    $description = get_string('rawscsspre_desc', 'theme_boost');
    $setting = new admin_setting_scsscode($name, $title, $description, '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS
    $name = 'theme_beit/scss';
    $title = get_string('rawscss', 'theme_boost');
    $description = get_string('rawscss_desc', 'theme_boost');
    $setting = new admin_setting_scsscode($name, $title, $description, '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
