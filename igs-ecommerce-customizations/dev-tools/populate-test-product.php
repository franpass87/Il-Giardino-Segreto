<?php
/**
 * DEV TOOL: Popola il prodotto test (ID 695) con tutti i meta IGS per test completi.
 * Eseguire UNA VOLTA visitando: /wp-content/plugins/igs-ecommerce-customizations/dev-tools/populate-test-product.php
 * (da loggato come admin).
 * Rimuovere prima del deploy in produzione.
 *
 * @package IGS_Ecommerce
 */

// Carica WordPress
$wp_load = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('WordPress non trovato.');
}
require_once $wp_load;

if (!current_user_can('manage_options')) {
    wp_die('Accesso negato.');
}

$productId = 695;
$product = wc_get_product($productId);
if (!$product) {
    wp_die('Prodotto 695 non trovato. Crea prima "Tour Test Italia".');
}

// Meta TourProductMetabox
update_post_meta($productId, '_date_ranges', [
    ['start' => '15/04/2026', 'end' => '17/04/2026'],
    ['start' => '22/05/2026', 'end' => '24/05/2026'],
]);
update_post_meta($productId, '_paese_tour', 'Italia');

// Meta GardenMetabox (Dettagli Garden Tour)
update_post_meta($productId, '_protagonista_tour', 'Peonia');
update_post_meta($productId, '_livello_culturale', '4');
update_post_meta($productId, '_livello_passeggiata', '3');
update_post_meta($productId, '_livello_piuma', '5');
update_post_meta($productId, '_livello_esclusivita', '4');

// Meta MapMetabox (Mappa del Viaggio)
update_post_meta($productId, '_mappa_paese', 'Italia');
update_post_meta($productId, '_mappa_tappe', [
    [
        'nome' => 'Alba',
        'lat' => '44.7009',
        'lon' => '8.0357',
        'descrizione' => 'Partenza del tour enogastronomico.',
    ],
    [
        'nome' => 'Barolo',
        'lat' => '44.6109',
        'lon' => '7.9422',
        'descrizione' => 'Visita alle cantine e degustazione.',
    ],
    [
        'nome' => 'La Morra',
        'lat' => '44.6377',
        'lon' => '7.9345',
        'descrizione' => 'Panorami sulle Langhe.',
    ],
]);

// Descrizione breve (usata in TourLayout hero)
$shortDesc = 'Tour di 3 giorni tra le Langhe: Alba, Barolo e La Morra. Esperienza enogastronomica con visite guidate e degustazioni.';

// Descrizione completa con shortcode per testare Garden e Mappa
$fullDesc = <<<HTML
<p>Tour Test Italia è un viaggio di prova per verificare tutte le funzionalità del plugin IGS Ecommerce.</p>

<h3>Pianta protagonista</h3>
[protagonista_tour]

<h3>Profilo del tour</h3>
[livello_culturale]
[livello_passeggiata]
[livello_piuma]
[livello_esclusivita]

<h3>Mappa del viaggio</h3>
[mappa_viaggio id="{$productId}"]
HTML;

wp_update_post([
    'ID' => $productId,
    'post_excerpt' => $shortDesc,
    'post_content' => $fullDesc,
    'post_status' => 'publish',
]);

echo '<h1>Prodotto test popolato</h1>';
echo '<p><a href="' . esc_url(get_permalink($productId)) . '">Vedi prodotto sul frontend</a></p>';
echo '<p><a href="' . esc_url(admin_url('post.php?post=' . $productId . '&action=edit')) . '">Modifica in admin</a></p>';
