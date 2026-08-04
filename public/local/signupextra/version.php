<?php
/**
 * Plugin local : personnalisation du formulaire d'inscription (signup).
 * - Ajoute un champ "Confirmer le mot de passe" avec validation.
 * - Retire le champ "Courriel (confirmation)".
 *
 * @package   local_signupextra
 */
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_signupextra';
$plugin->version   = 2026062100;
$plugin->requires  = 2024100700; // Moodle 4.5+/5.x
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';
