<?php
// Shared, idempotent Moodle Custom certificate artwork and layout.

defined('MOODLE_INTERNAL') || die();

/**
 * Store a project image in the system-wide Custom certificate image library.
 *
 * @return array<string, int|string> Metadata expected by image/bgimage elements.
 */
function isp_certificate_store_asset(string $filename, ?stored_file $source = null): array {
    global $USER;

    $contextid = context_system::instance()->id;
    $fs = get_file_storage();
    $file = $fs->get_file($contextid, 'mod_customcert', 'image', 0, '/', $filename);
    if (!$file) {
        $record = [
            'contextid' => $contextid,
            'component' => 'mod_customcert',
            'filearea' => 'image',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
            'license' => 'allrightsreserved',
        ];
        if ($source) {
            $file = $fs->create_file_from_storedfile($record, $source);
        } else {
            $path = __DIR__ . '/assets/' . $filename;
            if (!is_file($path)) {
                throw new RuntimeException("Certificate asset not found: {$path}");
            }
            $file = $fs->create_file_from_pathname($record, $path);
        }
    }

    return [
        'contextid' => $file->get_contextid(),
        'filearea' => $file->get_filearea(),
        'itemid' => $file->get_itemid(),
        'filepath' => $file->get_filepath(),
        'filename' => $file->get_filename(),
    ];
}

/** Apply the same print-ready template to an existing certificate activity. */
function isp_apply_certificate_template(stdClass $certificate): void {
    global $DB;

    $page = $DB->get_record('customcert_pages', ['templateid' => $certificate->templateid], '*', MUST_EXIST);
    if ((int)$page->width !== 297 || (int)$page->height !== 210) {
        $page->width = 297;
        $page->height = 210;
        $DB->update_record('customcert_pages', $page);
    }
    if (!(int)$certificate->verifyany) {
        $DB->set_field('customcert', 'verifyany', 1, ['id' => $certificate->id]);
    }

    $background = isp_certificate_store_asset('isp-certificate-background-v1.png');
    $signature = isp_certificate_store_asset('isp-signature-demo-v1.png');
    $systemcontextid = context_system::instance()->id;
    $logo = get_file_storage()->get_file(
        $systemcontextid, 'theme_moove', 'logo', 0, '/', 'isp-niger-logo.png'
    );
    if (!$logo) {
        throw new RuntimeException('The approved ISP logo must be installed before applying the certificate template.');
    }
    $logodata = isp_certificate_store_asset('isp-niger-logo-certificate.png', $logo);

    $navy = '#17324D';
    $burgundy = '#6E3A41';
    $muted = '#48576A';
    $gold = '#9F7B45';

    // Position is in millimetres on an A4 landscape page. The QR code and
    // issue code are live Moodle elements, never baked into the background.
    $layout = [
        ['Fond du certificat', 'bgimage', $background, 0, 0, 'L', 0],
        ['Ministère', 'text', ['text' => 'MINISTÈRE DE LA SANTÉ ET DE L\'HYGIÈNE PUBLIQUE',
            'font' => 'helvetica', 'fontsize' => 10, 'colour' => $burgundy, 'width' => 245], 148, 18, 'C', 1],
        ['Logo officiel ISP', 'image', $logodata + ['width' => 86, 'height' => 0, 'alphachannel' => 1],
            105, 29, 'L', 0],
        ['Titre du certificat', 'text', ['text' => 'CERTIFICAT DE RÉUSSITE',
            'font' => 'timesb', 'fontsize' => 24, 'colour' => $navy, 'width' => 250], 148, 59, 'C', 1],
        ['Sous-titre', 'text', ['text' => 'FORMATION CONTINUE ET DÉVELOPPEMENT DES COMPÉTENCES',
            'font' => 'helvetica', 'fontsize' => 9, 'colour' => $gold, 'width' => 250], 148, 77, 'C', 1],
        ['Mention attribué à', 'text', ['text' => "L'Institut de Santé Publique certifie que",
            'font' => 'helvetica', 'fontsize' => 12, 'colour' => $muted, 'width' => 240], 148, 91, 'C', 1],
        ['Nom de l’apprenant', 'studentname', ['font' => 'times', 'fontsize' => 27,
            'colour' => $burgundy, 'width' => 250], 148, 105, 'C', 1],
        ['Mention cours', 'text', ['text' => 'a suivi avec succès et satisfait aux exigences pédagogiques du cours',
            'font' => 'helvetica', 'fontsize' => 11, 'colour' => $muted, 'width' => 240], 148, 128, 'C', 1],
        ['Nom du cours', 'coursename', ['coursenamedisplay' => 2, 'font' => 'times',
            'fontsize' => 18, 'colour' => $navy, 'width' => 225], 148, 141, 'C', 1],
        ['Label date', 'text', ['text' => 'DÉLIVRÉ LE', 'font' => 'helvetica', 'fontsize' => 8,
            'colour' => $gold, 'width' => 70], 54, 170, 'C', 1],
        ['Date', 'date', ['dateitem' => '-1', 'dateformat' => 'strftimedate',
            'font' => 'helvetica', 'fontsize' => 10, 'colour' => $navy, 'width' => 75], 54, 180, 'C', 1],
        ['Signature de démonstration', 'image', $signature + ['width' => 58, 'height' => 0, 'alphachannel' => 1],
            119, 163, 'L', 0],
        ['Signature du Directeur Général', 'text', ['text' => 'Le Directeur Général',
            'font' => 'helvetica', 'fontsize' => 10, 'colour' => $navy, 'width' => 92], 148, 184, 'C', 1],
        ['QR code de vérification', 'qrcode', ['width' => 29, 'height' => 29], 241, 151, 'L', 0],
        ['Label vérification', 'text', ['text' => 'VÉRIFIER EN LIGNE',
            'font' => 'helvetica', 'fontsize' => 8, 'colour' => $navy, 'width' => 67], 255, 182, 'C', 1],
        ['Code de vérification', 'code', ['font' => 'helvetica', 'fontsize' => 8,
            'colour' => $muted, 'width' => 67], 255, 189, 'C', 1],
    ];

    foreach ($layout as $sequence => [$name, $type, $data, $x, $y, $alignment, $refpoint]) {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $element = $DB->get_record('customcert_elements', ['pageid' => $page->id, 'name' => $name]);
        if (!$element) {
            $element = (object)[
                'pageid' => $page->id,
                'name' => $name,
                'timecreated' => time(),
            ];
        }
        $changed = !isset($element->id)
            || $element->element !== $type
            || $element->data !== $encoded
            || (float)$element->posx !== (float)$x
            || (float)$element->posy !== (float)$y
            || $element->alignment !== $alignment
            || (int)$element->refpoint !== $refpoint
            || (int)$element->sequence !== $sequence + 1;
        if (!$changed) {
            continue;
        }
        $element->element = $type;
        $element->data = $encoded;
        $element->posx = $x;
        $element->posy = $y;
        $element->alignment = $alignment;
        $element->refpoint = $refpoint;
        $element->sequence = $sequence + 1;
        $element->timemodified = time();
        if (isset($element->id)) {
            $DB->update_record('customcert_elements', $element);
        } else {
            $DB->insert_record('customcert_elements', $element);
        }
    }

    // The previous text-only institute heading duplicates the official logo.
    $DB->delete_records('customcert_elements', [
        'pageid' => $page->id,
        'name' => 'Institut de Santé Publique',
    ]);
    $DB->delete_records('customcert_elements', [
        'pageid' => $page->id,
        'name' => 'Mention signature de démonstration',
    ]);
}
