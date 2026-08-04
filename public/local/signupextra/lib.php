<?php
/**
 * Callbacks de personnalisation du formulaire d'inscription.
 *
 * @package   local_signupextra
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Modifie le formulaire d'inscription :
 *  - ajoute un champ "Confirmer le mot de passe" après le champ mot de passe ;
 *  - retire le champ "Courriel (confirmation)" (email2).
 *
 * @param MoodleQuickForm $mform
 */
function local_signupextra_extend_signup_form($mform) {
    // 1) Masquer la confirmation d'adresse de courriel.
    // Le core (signup_validate_data) exige toujours que email2 == email ;
    // on remplace donc le champ visible par un champ caché. La recopie
    // email -> email2 est faite côté client (voir additionalhtmlhead dans
    // config.php) ET, en filet de sécurité, côté serveur ci-dessous.
    if ($mform->elementExists('email2')) {
        $mform->removeElement('email2');
    }
    $mform->addElement('hidden', 'email2', '');
    $mform->setType('email2', core_user::get_property_type('email'));

    // 2) Ajouter un champ de confirmation du mot de passe.
    if ($mform->elementExists('password') && !$mform->elementExists('password2')) {
        $element = $mform->createElement(
            'password',
            'password2',
            get_string('password2', 'local_signupextra'),
            [
                'maxlength' => 32,
                'size' => 20,
                'autocomplete' => 'new-password',
            ]
        );
        // Insérer juste avant le champ "email" (donc juste après "password").
        if ($mform->elementExists('email')) {
            $mform->insertElementBefore($element, 'email');
        } else {
            $mform->addElement($element);
        }
        $mform->setType('password2', core_user::get_property_type('password'));
        $mform->addRule(
            'password2',
            get_string('missingpassword2', 'local_signupextra'),
            'required',
            null,
            'client'
        );
    }
}

/**
 * Valide la confirmation du mot de passe.
 *
 * @param array $data données soumises
 * @return array tableau d'erreurs (champ => message)
 */
function local_signupextra_validate_extend_signup_form($data) {
    $errors = [];
    $p1 = isset($data['password']) ? $data['password'] : '';
    $p2 = isset($data['password2']) ? $data['password2'] : '';
    if ($p2 === '' && $p1 !== '') {
        $errors['password2'] = get_string('missingpassword2', 'local_signupextra');
    } else if ($p1 !== $p2) {
        $errors['password2'] = get_string('passwordsdiffer', 'local_signupextra');
    }
    return $errors;
}
